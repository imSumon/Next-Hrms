<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PunchEvent;
use App\Models\PunchIdentity;
use App\Models\PunchIdentityOverlap;
use App\Models\PunchRecordingWriter;
use App\Models\User;
use App\Punch\DeviceEventIngestion;
use App\Punch\PunchRecordingRegistry;
use App\Tenancy\CompanyContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Tests\EmployeeDatabaseTestCase;

class DeviceEventIngestionTest extends EmployeeDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-20T12:00:00+06:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_a_row_is_stored_only_against_the_registered_identity_and_leaves_it_unchanged(): void
    {
        $company = $this->company('Synthetic Ingest Store');
        $user = $this->actAsWriter($company);
        $employee = $this->employee($company, 'ING-1');
        $identity = $this->identity($company, $employee, 'CARD-1', '2026-10-01T00:00:00+06:00', '2026-10-10T00:00:00+06:00');
        $before = $this->identitySnapshot([$company->id]);

        $result = $this->ingest('all_att_log:1', ' CARD-1 ', '2026-10-05T08:55:00+06:00');

        $this->assertSame(DeviceEventIngestion::RECORDED, $result['outcome']);
        $this->assertSame($identity->id, $result['punch_identity_id']);
        $this->assertSame($employee->id, $result['employee_id']);
        $event = PunchEvent::query()->findOrFail($result['event_id']);
        $this->assertSame($company->id, $event->company_id);
        $this->assertSame('all_att_log:1', $event->source_event_id);
        $this->assertSame((string) config('attendance.device_log.source_name'), $event->source_name);
        $this->assertSame($user->id, $event->acting_user_id);
        $this->assertSame('2026-10-05 02:55:00', $event->event_at?->copy()->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-20 06:00:00', $event->ingested_at?->copy()->utc()->format('Y-m-d H:i:s'));
        $this->assertSame($before, $this->identitySnapshot([$company->id]));
        $this->assertSame(1, AuditEvent::query()->where('company_id', $company->id)->where('action_kind', AuditEvent::ACTION_PUNCH_EVENT_RECORDED)->count());
        $this->assertSame(0, AuditEvent::query()->where('company_id', $company->id)->where('subject_kind', AuditEvent::SUBJECT_PUNCH_IDENTITY)->count());
    }

    public function test_the_company_comes_from_the_trusted_context_and_needs_membership_and_the_writer_grant(): void
    {
        $own = $this->company('Synthetic Ingest Auth');
        $other = $this->company('Synthetic Ingest Auth Other');
        $employee = $this->employee($other, 'ING-AUTH');
        $this->identity($other, $employee, 'CARD-AUTH', '2026-10-01T00:00:00+06:00', null);

        $user = $this->signInFor($own, false);
        $attempts = [
            'no context' => fn () => $this->app->forgetInstance(CompanyContext::class),
            'member without grant' => fn () => $this->app->instance(CompanyContext::class, new CompanyContext($own->id)),
            'context of a company without membership' => function () use ($own, $other, $user): void {
                PunchRecordingWriter::query()->create(['user_id' => $user->id, 'company_id' => $own->id, 'authorized_at' => now()]);
                $this->app->instance(CompanyContext::class, new CompanyContext($other->id));
            },
        ];
        foreach ($attempts as $label => $arrange) {
            $arrange();
            try {
                $this->ingest('all_att_log:70', 'CARD-AUTH', '2026-10-05T09:00:00+06:00');
                $this->fail('Ingestion was allowed with '.$label.'.');
            } catch (AuthorizationException) {
            }
        }

        Auth::logout();
        $this->app->instance(CompanyContext::class, new CompanyContext($own->id));
        try {
            $this->ingest('all_att_log:71', 'CARD-AUTH', '2026-10-05T09:00:00+06:00');
            $this->fail('Ingestion was allowed without a signed-in user.');
        } catch (AuthorizationException) {
        }
        $this->assertSame(0, PunchEvent::query()->whereIn('company_id', [$own->id, $other->id])->count());
    }

    public function test_ingestion_never_creates_moves_or_extends_an_identity(): void
    {
        $company = $this->company('Synthetic Ingest Bounds');
        $this->actAsWriter($company);
        $employee = $this->employee($company, 'ING-2');
        $this->identity($company, $employee, 'CARD-2', '2026-10-05T00:00:00+06:00', '2026-10-06T00:00:00+06:00');
        $before = $this->identitySnapshot([$company->id]);

        $unknown = $this->ingest('all_att_log:10', 'CARD-NEW', '2026-10-05T09:00:00+06:00');
        $beforeStart = $this->ingest('all_att_log:11', 'CARD-2', '2026-10-04T09:00:00+06:00');
        $afterEnd = $this->ingest('all_att_log:12', 'CARD-2', '2026-10-06T00:00:00+06:00');

        foreach ([$unknown, $beforeStart, $afterEnd] as $result) {
            $this->assertSame(DeviceEventIngestion::UNMAPPED, $result['outcome']);
            $this->assertNull($result['event_id']);
            $this->assertStringContainsString('Nothing was created', (string) $result['message']);
        }
        $this->assertSame($before, $this->identitySnapshot([$company->id]));
        $this->assertSame(0, PunchEvent::query()->where('company_id', $company->id)->count());
        $this->assertSame(0, AuditEvent::query()->where('company_id', $company->id)->count());
    }

    public function test_ingestion_never_transfers_a_card_or_chooses_between_holders(): void
    {
        $own = $this->company('Synthetic Ingest Own');
        $other = $this->company('Synthetic Ingest Other');
        $first = $this->employee($own, 'ING-3A');
        $second = $this->employee($own, 'ING-3B');
        $foreign = $this->employee($other, 'ING-3X');
        $this->overlappingHolders($own, $first, $second, 'CARD-OVERLAP');
        $this->identity($own, $first, 'CARD-SHARED', '2026-10-01T00:00:00+06:00', null);
        $this->identity($other, $foreign, 'CARD-SHARED', '2026-10-01T00:00:00+06:00', null);
        $this->identity($other, $foreign, 'CARD-FOREIGN', '2026-10-01T00:00:00+06:00', null);
        $before = $this->identitySnapshot([$own->id, $other->id]);

        $this->actAsWriter($own);
        $overlap = $this->ingest('all_att_log:20', 'CARD-OVERLAP', '2026-10-05T09:00:00+06:00');
        $shared = $this->ingest('all_att_log:21', 'CARD-SHARED', '2026-10-05T09:00:00+06:00');
        $foreignOnly = $this->ingest('all_att_log:22', 'CARD-FOREIGN', '2026-10-05T09:00:00+06:00');
        $this->actAsWriter($other);
        $sharedFromOther = $this->ingest('all_att_log:21', 'CARD-SHARED', '2026-10-05T09:00:00+06:00');

        $this->assertSame(DeviceEventIngestion::AMBIGUOUS, $overlap['outcome']);
        $this->assertStringContainsString('does not choose', (string) $overlap['message']);
        $this->assertSame(DeviceEventIngestion::AMBIGUOUS, $shared['outcome']);
        $this->assertSame(DeviceEventIngestion::AMBIGUOUS, $sharedFromOther['outcome']);
        $this->assertStringContainsString('Another company', (string) $shared['message']);
        $this->assertSame(DeviceEventIngestion::UNMAPPED, $foreignOnly['outcome']);
        foreach ([$overlap, $shared, $foreignOnly, $sharedFromOther] as $result) {
            $this->assertNull($result['employee_id']);
            $this->assertNull($result['event_id']);
        }
        $this->assertSame($before, $this->identitySnapshot([$own->id, $other->id]));
        $this->assertSame(0, PunchEvent::query()->whereIn('company_id', [$own->id, $other->id])->count());
    }

    public function test_card_values_are_compared_exactly_without_zero_stripping(): void
    {
        $company = $this->company('Synthetic Ingest Exact');
        $this->actAsWriter($company);
        $employee = $this->employee($company, 'ING-Z');
        $this->identity($company, $employee, '0SYN98765', '2026-10-01T00:00:00+06:00', null);

        $stripped = $this->ingest('all_att_log:80', 'SYN98765', '2026-10-05T09:00:00+06:00');
        $exact = $this->ingest('all_att_log:81', '0SYN98765', '2026-10-05T09:00:00+06:00');

        $this->assertSame(DeviceEventIngestion::UNMAPPED, $stripped['outcome']);
        $this->assertSame(DeviceEventIngestion::RECORDED, $exact['outcome']);
        $this->assertSame(1, PunchIdentity::query()->where('company_id', $company->id)->count());
    }

    public function test_unreadable_times_and_incomplete_rows_are_refused_with_a_reason(): void
    {
        $company = $this->company('Synthetic Ingest Invalid');
        $this->actAsWriter($company);
        $employee = $this->employee($company, 'ING-4');
        $this->identity($company, $employee, 'CARD-4', '2026-01-01T00:00:00+06:00', null);

        $cases = [
            ['all_att_log:30', 'CARD-4', '2026-10-05 08:55:00', DeviceEventIngestion::INVALID_TIMESTAMP],
            ['all_att_log:31', 'CARD-4', '2026-10-05T08:55:00', DeviceEventIngestion::INVALID_TIMESTAMP],
            ['all_att_log:32', 'CARD-4', '2026-02-30T08:00:00+06:00', DeviceEventIngestion::INVALID_TIMESTAMP],
            ['all_att_log:33', 'CARD-4', '0000-00-00T00:00:00+06:00', DeviceEventIngestion::INVALID_TIMESTAMP],
            ['all_att_log:34', 'CARD-4', '2026-10-05T25:00:00+06:00', DeviceEventIngestion::INVALID_TIMESTAMP],
            ['all_att_log:35', 'CARD-4', '2026-10-21T08:00:00+06:00', DeviceEventIngestion::INVALID_TIMESTAMP],
            ['all_att_log:36', ' ', '2026-10-05T08:55:00+06:00', DeviceEventIngestion::INVALID_IDENTITY],
            [null, 'CARD-4', '2026-10-05T08:55:00+06:00', DeviceEventIngestion::INVALID_SOURCE_EVENT],
            ['  ', 'CARD-4', '2026-10-05T08:55:00+06:00', DeviceEventIngestion::INVALID_SOURCE_EVENT],
            ['2', 'CARD-4', '2026-10-05T08:55:00+06:00', DeviceEventIngestion::INVALID_SOURCE_EVENT],
            ['all_att_log:0', 'CARD-4', '2026-10-05T08:55:00+06:00', DeviceEventIngestion::INVALID_SOURCE_EVENT],
            ['all_att_log:02', 'CARD-4', '2026-10-05T08:55:00+06:00', DeviceEventIngestion::INVALID_SOURCE_EVENT],
            ['ALL_ATT_LOG:2', 'CARD-4', '2026-10-05T08:55:00+06:00', DeviceEventIngestion::INVALID_SOURCE_EVENT],
        ];
        foreach ($cases as [$sourceEventId, $card, $eventAt, $expected]) {
            $result = $this->ingest($sourceEventId, $card, $eventAt);
            $this->assertSame($expected, $result['outcome'], (string) $eventAt);
            $this->assertNotNull($result['message']);
            $this->assertNull($result['event_id']);
        }
        $this->assertSame(0, PunchEvent::query()->where('company_id', $company->id)->count());
        $this->assertSame(0, AuditEvent::query()->where('company_id', $company->id)->count());
    }

    public function test_source_event_ids_must_be_the_canonical_all_att_log_form(): void
    {
        $company = $this->company('Synthetic Ingest Canonical Id');
        $this->actAsWriter($company);
        $employee = $this->employee($company, 'ING-ID');
        $this->identity($company, $employee, 'CARD-ID', '2026-01-01T00:00:00+06:00', null);

        $accepted = $this->ingest(' all_att_log:52976952 ', 'CARD-ID', '2026-10-05T08:55:00+06:00');
        $this->assertSame(DeviceEventIngestion::RECORDED, $accepted['outcome']);
        $this->assertSame('all_att_log:52976952', PunchEvent::query()->findOrFail($accepted['event_id'])->source_event_id);

        $rejected = [
            '2',
            'all_att_log:0',
            'all_att_log:02',
            'ALL_ATT_LOG:2',
            '',
            '  ',
            null,
        ];
        foreach ($rejected as $sourceEventId) {
            $result = $this->ingest($sourceEventId, 'CARD-ID', '2026-10-05T09:00:00+06:00');
            $this->assertSame(DeviceEventIngestion::INVALID_SOURCE_EVENT, $result['outcome'], (string) $sourceEventId);
            $this->assertNotNull($result['message']);
            $this->assertNull($result['event_id']);
        }

        $this->assertSame(['all_att_log:52976952'], PunchEvent::query()->where('company_id', $company->id)->pluck('source_event_id')->all());
        $this->assertSame(1, AuditEvent::query()->where('company_id', $company->id)->count());
    }

    public function test_repeats_are_checked_first_and_changed_repeats_are_conflicts(): void
    {
        $company = $this->company('Synthetic Ingest Repeat');
        $other = $this->company('Synthetic Ingest Repeat Other');
        $employee = $this->employee($company, 'ING-5');
        $foreign = $this->employee($other, 'ING-5X');
        $this->identity($company, $employee, 'CARD-5', '2026-10-01T00:00:00+06:00', null);
        $this->identity($company, $employee, 'CARD-5B', '2026-10-01T00:00:00+06:00', null);

        $this->actAsWriter($company);
        $first = $this->ingest('all_att_log:40', 'CARD-5', '2026-10-05T08:55:00+06:00');
        $repeat = $this->ingest('all_att_log:40', 'CARD-5', '2026-10-05T08:55:00+06:00');
        $changedTime = $this->ingest('all_att_log:40', 'CARD-5', '2026-10-05T09:30:00+06:00');
        $changedCard = $this->ingest('all_att_log:40', 'CARD-5B', '2026-10-05T08:55:00+06:00');
        $unreadableRepeat = $this->ingest('all_att_log:40', 'CARD-5', '2026-10-05 08:55:00');
        $sameTimeNewId = $this->ingest('all_att_log:41', 'CARD-5', '2026-10-05T08:55:00+06:00');
        app(PunchRecordingRegistry::class)->record($company->id, [
            'employee_id' => $employee->id,
            'identity_value' => 'CARD-5',
            'source_name' => 'Synthetic second reader',
            'event_at' => '2026-10-05T18:00:00+06:00',
            'source_event_id' => 'reader:7',
        ]);
        $sameTimeOtherSource = $this->ingest('all_att_log:43', 'CARD-5', '2026-10-05T18:00:00+06:00');
        $later = $this->ingest('all_att_log:42', 'CARD-5', '2026-10-05T18:05:00+06:00');

        $this->identity($other, $foreign, 'CARD-5', '2026-10-06T00:00:00+06:00', null);
        $this->actAsWriter($other);
        $elsewhere = $this->ingest('all_att_log:40', 'CARD-5', '2026-10-05T08:55:00+06:00');

        $this->assertSame(DeviceEventIngestion::RECORDED, $first['outcome']);
        $this->assertSame(DeviceEventIngestion::ALREADY_RECORDED, $repeat['outcome']);
        $this->assertSame(DeviceEventIngestion::SOURCE_EVENT_CONFLICT, $changedTime['outcome']);
        $this->assertSame(DeviceEventIngestion::SOURCE_EVENT_CONFLICT, $changedCard['outcome']);
        $this->assertSame(DeviceEventIngestion::SOURCE_EVENT_CONFLICT, $unreadableRepeat['outcome']);
        $this->assertSame(DeviceEventIngestion::POSSIBLE_DUPLICATE, $sameTimeNewId['outcome']);
        $this->assertSame(DeviceEventIngestion::POSSIBLE_DUPLICATE, $sameTimeOtherSource['outcome']);
        $this->assertStringContainsString('PUNCH-CONF-002', (string) $sameTimeOtherSource['message']);
        $this->assertSame(DeviceEventIngestion::RECORDED, $later['outcome']);
        $this->assertSame(DeviceEventIngestion::SOURCE_EVENT_CONFLICT, $elsewhere['outcome']);
        $this->assertSame(
            ['all_att_log:40', 'reader:7', 'all_att_log:42'],
            PunchEvent::query()->where('company_id', $company->id)->orderBy('event_at')->pluck('source_event_id')->all(),
        );
        $this->assertSame(0, PunchEvent::query()->where('company_id', $other->id)->count());
    }

    public function test_the_shared_writer_cannot_be_used_to_bypass_device_safeguards(): void
    {
        $company = $this->company('Synthetic Ingest Guard');
        $other = $this->company('Synthetic Ingest Guard Other');
        $user = $this->actAsWriter($company);
        $first = $this->employee($company, 'ING-6A');
        $second = $this->employee($company, 'ING-6B');
        $foreign = $this->employee($other, 'ING-6X');
        $this->overlappingHolders($company, $first, $second, 'CARD-6-OVERLAP');
        $this->identity($company, $first, 'CARD-6-SHARED', '2026-10-01T00:00:00+06:00', null);
        $this->identity($other, $foreign, 'CARD-6-SHARED', '2026-10-01T00:00:00+06:00', null);
        $this->identity($company, $first, 'CARD-6', '2026-10-05T00:00:00+06:00', '2026-10-06T00:00:00+06:00');
        $registry = app(PunchRecordingRegistry::class);
        $before = $this->identitySnapshot([$company->id, $other->id]);

        $this->assertTrue((new \ReflectionMethod(PunchRecordingRegistry::class, 'appendEvent'))->isPrivate());
        $misuses = [
            'overlapping holders' => ['CARD-6-OVERLAP', '2026-10-05T09:00:00+06:00'],
            'another company holds the card' => ['CARD-6-SHARED', '2026-10-05T09:00:00+06:00'],
            'time outside the mapping' => ['CARD-6', '2026-10-04T09:00:00+06:00'],
            'unregistered card' => ['CARD-6-NONE', '2026-10-05T09:00:00+06:00'],
        ];
        foreach ($misuses as $label => [$card, $eventAt]) {
            try {
                $registry->appendDeviceEvent($card, Carbon::parse($eventAt), 'misuse:'.$card);
                $this->fail('The shared writer accepted '.$label.'.');
            } catch (\LogicException) {
            }
        }
        $stored = $registry->appendDeviceEvent('CARD-6', Carbon::parse('2026-10-05T09:00:00+06:00'), 'misuse:ok');
        $this->assertSame($user->id, $stored->acting_user_id);
        $this->assertSame($company->id, $stored->company_id);
        try {
            $registry->appendDeviceEvent('CARD-6', Carbon::parse('2026-10-05T10:00:00+06:00'), 'misuse:ok');
            $this->fail('The shared writer stored a repeated source event id.');
        } catch (\LogicException) {
        }

        try {
            $registry->record($company->id, [
                'employee_id' => $first->id,
                'identity_value' => 'CARD-6-NONE',
                'source_name' => (string) config('attendance.device_log.source_name'),
                'event_at' => '2026-10-05T09:00:00+06:00',
                'source_event_id' => 'all_att_log:90',
            ]);
            $this->fail('Manual recording accepted a device-log row.');
        } catch (ValidationException $exception) {
            $this->assertSame('Device-log rows are recorded only through the device import boundary.', $exception->errors()['source_name'][0]);
        }

        $this->assertSame($before, $this->identitySnapshot([$company->id, $other->id]));
        $this->assertSame(['misuse:ok'], PunchEvent::query()->whereIn('company_id', [$company->id, $other->id])->pluck('source_event_id')->all());
    }

    public function test_the_shared_writer_takes_company_and_actor_only_from_the_authorized_context(): void
    {
        $own = $this->company('Synthetic Direct Own');
        $other = $this->company('Synthetic Direct Other');
        $ownEmployee = $this->employee($own, 'DIR-1');
        $otherEmployee = $this->employee($other, 'DIR-2');
        $this->identity($own, $ownEmployee, 'CARD-DIR', '2026-10-01T00:00:00+06:00', null);
        $this->identity($other, $otherEmployee, 'CARD-DIR-OTHER', '2026-10-01T00:00:00+06:00', null);
        $registry = app(PunchRecordingRegistry::class);
        $write = fn (string $card, string $id) => $registry->appendDeviceEvent($card, Carbon::parse('2026-10-05T09:00:00+06:00'), $id);

        $user = $this->signInFor($own, false);
        $attempts = [
            'no context' => [fn () => $this->app->forgetInstance(CompanyContext::class), 'CARD-DIR'],
            'member without grant' => [fn () => $this->app->instance(CompanyContext::class, new CompanyContext($own->id)), 'CARD-DIR'],
            'context of a company without membership' => [function () use ($own, $other, $user): void {
                PunchRecordingWriter::query()->create(['user_id' => $user->id, 'company_id' => $own->id, 'authorized_at' => now()]);
                $this->app->instance(CompanyContext::class, new CompanyContext($other->id));
            }, 'CARD-DIR-OTHER'],
        ];
        foreach ($attempts as $label => [$arrange, $card]) {
            $arrange();
            try {
                $write($card, 'direct:'.$label);
                $this->fail('The shared writer was usable with '.$label.'.');
            } catch (AuthorizationException) {
            }
        }

        $this->app->instance(CompanyContext::class, new CompanyContext($own->id));
        Auth::logout();
        try {
            $write('CARD-DIR', 'direct:signed-out');
            $this->fail('The shared writer was usable without a signed-in user.');
        } catch (AuthorizationException) {
        }
        $this->assertSame(0, PunchEvent::query()->whereIn('company_id', [$own->id, $other->id])->count());
    }

    public function test_manual_recording_refuses_device_log_rows_in_any_spelling_and_still_records_manual_punches(): void
    {
        $company = $this->company('Synthetic Manual Separation');
        $this->actAsWriter($company);
        $employee = $this->employee($company, 'MAN-1');
        $registry = app(PunchRecordingRegistry::class);
        $device = (string) config('attendance.device_log.source_name');
        $attempts = [
            [strtoupper($device), 'SRC-A'],
            ['  '.str_replace(' ', '   ', $device).'  ', 'SRC-B'],
            [$device.' (manual)', 'SRC-C'],
            ['Uploaded ALL_ATT_LOG copy', 'SRC-D'],
            ['Gate reader', 'ALL_ATT_LOG:77'],
            ['Gate reader', ' all_att_log:78'],
        ];
        foreach ($attempts as [$sourceName, $sourceEventId]) {
            try {
                $registry->record($company->id, [
                    'employee_id' => $employee->id,
                    'identity_value' => 'CARD-MAN',
                    'source_name' => $sourceName,
                    'event_at' => '2026-10-05T09:00:00+06:00',
                    'source_event_id' => $sourceEventId,
                ]);
                $this->fail('Manual recording accepted a device-log row: '.$sourceName.' / '.$sourceEventId);
            } catch (ValidationException $exception) {
                $this->assertSame('Device-log rows are recorded only through the device import boundary.', $exception->errors()['source_name'][0]);
            }
        }
        $this->assertSame(0, PunchIdentity::query()->where('company_id', $company->id)->count());
        $this->assertSame(0, PunchEvent::query()->where('company_id', $company->id)->count());

        $manual = $registry->record($company->id, [
            'employee_id' => $employee->id,
            'identity_value' => 'CARD-MAN',
            'source_name' => 'Gate reader',
            'event_at' => '2026-10-05T09:00:00+06:00',
            'source_event_id' => 'SRC-1',
        ]);

        $this->assertSame('Gate reader', $manual->source_name);
        $this->assertSame($employee->id, $manual->identity?->employee_id);
        $this->assertSame(1, PunchIdentity::query()->where('company_id', $company->id)->where('identity_value', 'CARD-MAN')->count());
    }

    public function test_device_log_routes_stay_on_the_preview_and_batch_import_boundary(): void
    {
        $deviceRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route): bool => str_contains($route->uri(), 'device-log'));

        $this->assertNotEmpty($deviceRoutes);
        foreach ($deviceRoutes as $route) {
            if (str_contains($route->uri(), 'device-log/import')) {
                $this->assertSame(['POST'], $route->methods(), $route->uri());

                continue;
            }
            $this->assertSame(['GET', 'HEAD'], $route->methods(), $route->uri());
        }
        $this->assertFalse(str_contains((string) file_get_contents(base_path('routes/web.php')), 'DeviceEventIngestion'));
    }

    /**
     * @return array{outcome: string, message: ?string, event_id: ?string, punch_identity_id: ?string, employee_id: ?string}
     */
    private function ingest(?string $sourceEventId, ?string $card, ?string $eventAt): array
    {
        return app(DeviceEventIngestion::class)->ingest($sourceEventId, $card, $eventAt);
    }

    private function actAsWriter(Company $company): User
    {
        $user = $this->signInFor($company, false);
        PunchRecordingWriter::query()->create(['user_id' => $user->id, 'company_id' => $company->id, 'authorized_at' => now()]);
        $this->app->instance(CompanyContext::class, new CompanyContext($company->id));

        return $user;
    }

    private function overlappingHolders(Company $company, Employee $first, Employee $second, string $card): void
    {
        $this->identity($company, $first, $card, '2026-10-01T00:00:00+06:00', null);
        $this->identity($company, $second, $card, '2026-10-01T00:00:00+06:00', null);
        PunchIdentityOverlap::query()->create([
            'company_id' => $company->id,
            'identity_value' => $card,
            'effective_from' => '2026-10-01T00:00:00+06:00',
            'effective_to' => '2026-10-31T00:00:00+06:00',
            'reason' => 'Synthetic documented overlap.',
        ]);
    }

    /**
     * @param  list<string>  $companyIds
     * @return list<array<string, mixed>>
     */
    private function identitySnapshot(array $companyIds): array
    {
        return PunchIdentity::query()
            ->whereIn('company_id', $companyIds)
            ->orderBy('id')
            ->get()
            ->map(fn (PunchIdentity $identity): array => [
                'id' => $identity->id,
                'company_id' => $identity->company_id,
                'employee_id' => $identity->employee_id,
                'identity_value' => $identity->identity_value,
                'effective_from' => $identity->getRawOriginal('effective_from'),
                'effective_to' => $identity->getRawOriginal('effective_to'),
            ])
            ->all();
    }

    private function identity(Company $company, Employee $employee, string $value, string $from, ?string $to): PunchIdentity
    {
        return PunchIdentity::query()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'identity_value' => $value,
            'effective_from' => Carbon::parse($from)->utc(),
            'effective_to' => $to === null ? null : Carbon::parse($to)->utc(),
        ]);
    }

    private function company(string $name): Company
    {
        return Company::query()->create(['company_name' => $name, 'status' => 'Active']);
    }

    private function employee(Company $company, string $code): Employee
    {
        return Employee::query()->create([
            'company_id' => $company->id,
            'employee_code' => $code,
            'display_name' => 'Synthetic '.$code,
        ]);
    }
}
