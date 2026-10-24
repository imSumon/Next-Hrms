<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePunchRecordingWriter;
use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Employee;
use App\Models\PunchEvent;
use App\Models\PunchIdentity;
use App\Models\PunchIdentityOverlap;
use App\Models\PunchRecordingWriter;
use App\Models\User;
use App\Punch\PunchRecordingRegistry;
use App\Tenancy\CompanyContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\EmployeeDatabaseTestCase;

class PunchRecordingTest extends EmployeeDatabaseTestCase
{
    public function test_membership_flags_do_not_grant_punch_recording(): void
    {
        $company = $this->company('Synthetic Punch Workshop');
        $this->signInFor($company, true, 'active', true);
        $employee = $this->employee($company, 'P-1', 'Punch One');

        $this->getJson('/api/punch-events')
            ->assertForbidden()
            ->assertJsonPath('message', 'Punch recording is not authorized. No writer has been named.');
        $this->postJson('/api/punch-events', $this->payload($employee, 'CARD-1'))
            ->assertForbidden()
            ->assertJsonPath('message', 'Punch recording is not authorized. No writer has been named.');

        $this->assertSame(0, PunchEvent::query()->where('company_id', $company->id)->count());
    }

    public function test_a_guest_cannot_record_a_punch(): void
    {
        $this->postJson('/api/punch-events', [
            'employee_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV',
            'identity_value' => 'CARD-1',
            'source_name' => 'Gate reader',
            'event_at' => '2026-10-01T08:00:00+06:00',
        ])->assertUnauthorized();
    }

    public function test_the_engine_stores_one_immutable_event_for_the_active_company(): void
    {
        $north = $this->company('Synthetic North Punch');
        $south = $this->company('Synthetic South Punch');
        $user = $this->signInFor($north, false);
        $employee = $this->employee($north, 'N-1', 'North One');
        $this->employee($south, 'S-1', 'South One');
        Carbon::setTestNow(Carbon::parse('2026-10-18T09:30:00+06:00'));

        try {
            $event = $this->registry()->record($north->id, $this->payload($employee, 'CARD-NORTH', 'SRC-1'));
        } finally {
            Carbon::setTestNow();
        }
        $event->refresh();
        $event->load('identity');

        $this->assertSame($employee->id, $event->identity?->employee_id);
        $this->assertSame('CARD-NORTH', $event->identity?->identity_value);
        $this->assertNotSame($employee->employee_code, $event->identity?->identity_value);
        $this->assertSame('Gate reader', $event->source_name);
        $this->assertSame($user->id, $event->acting_user_id);
        $this->assertSame('accepted', $event->withdrawal_status);
        $this->assertSame('SRC-1', $event->source_event_id);
        $this->assertSame(
            Carbon::parse('2026-10-01T08:00:00+06:00')->utc()->format('Y-m-d H:i:s'),
            $event->event_at?->copy()->utc()->format('Y-m-d H:i:s'),
        );
        $this->assertSame(
            Carbon::parse('2026-10-18T09:30:00+06:00')->utc()->format('Y-m-d H:i:s'),
            $event->ingested_at?->copy()->utc()->format('Y-m-d H:i:s'),
        );
        $this->assertFalse($event->event_at?->equalTo($event->ingested_at));
        $this->assertFalse(Schema::hasColumn('punch_events', 'work_date'));

        $this->assertNull($this->registry()->find($south->id, (string) $event->id));
        $this->assertCount(0, $this->registry()->list($south->id));
        $this->assertCount(1, $this->registry()->list($north->id));

        $audit = AuditEvent::query()
            ->where('company_id', $north->id)
            ->where('subject_kind', AuditEvent::SUBJECT_PUNCH_EVENT)
            ->where('subject_id', $event->id)
            ->first();
        $this->assertNotNull($audit);
        $this->assertSame(AuditEvent::ACTION_PUNCH_EVENT_RECORDED, $audit->action_kind);
        $this->assertSame($user->id, $audit->acting_user_id);
        $this->assertNull($audit->previous_value);
        $this->assertSame('Gate reader', $audit->new_value['source_name']);
        $this->assertSame($user->id, $audit->new_value['acting_user_id']);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Punch events are immutable.');
        PunchEvent::query()->where('id', $event->id)->update(['source_name' => 'Changed']);
    }

    public function test_a_stored_event_cannot_be_deleted(): void
    {
        $company = $this->company('Synthetic Delete Punch');
        $this->signInFor($company, false);
        $employee = $this->employee($company, 'D-1', 'Delete One');
        $event = $this->registry()->record($company->id, $this->payload($employee, 'CARD-DEL'));

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Punch events are immutable.');
        PunchEvent::query()->where('id', $event->id)->delete();
    }

    public function test_an_identity_active_for_two_employees_is_rejected_unless_an_overlap_is_recorded(): void
    {
        $company = $this->company('Synthetic Identity Punch');
        $this->signInFor($company, false);
        $first = $this->employee($company, 'I-1', 'Identity One');
        $second = $this->employee($company, 'I-2', 'Identity Two');
        $when = '2026-10-01T08:00:00+06:00';

        $this->registry()->record($company->id, $this->payload($first, 'CARD-SHARED', null, $when));

        try {
            $this->registry()->record($company->id, $this->payload($second, 'CARD-SHARED', null, $when));
            $this->fail('A shared punch identity was accepted without an overlap.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'This punch identity is already active for another employee in this company.',
                $exception->errors()['identity_value'][0],
            );
        }
        $this->assertSame(1, PunchEvent::query()->where('company_id', $company->id)->count());

        PunchIdentityOverlap::query()->create([
            'company_id' => $company->id,
            'identity_value' => 'CARD-SHARED',
            'effective_from' => '2026-10-01T00:00:00+06:00',
            'effective_to' => '2026-10-02T00:00:00+06:00',
            'reason' => 'A recorded overlap for this test interval.',
        ]);

        $accepted = $this->registry()->record($company->id, $this->payload($second, 'CARD-SHARED', null, $when));
        $this->assertSame($second->id, $accepted->identity?->employee_id);
        $this->assertSame(2, PunchEvent::query()->where('company_id', $company->id)->count());
    }

    public function test_one_employee_can_hold_a_second_identity_and_an_earlier_event_keeps_the_same_mapping(): void
    {
        $company = $this->company('Synthetic Earlier Punch');
        $this->signInFor($company, false);
        $employee = $this->employee($company, 'E-1', 'Earlier One');

        $later = $this->registry()->record($company->id, $this->payload($employee, 'CARD-EARLY', null, '2026-10-02T08:00:00+06:00'));
        $also = $this->registry()->record($company->id, $this->payload($employee, 'CARD-OTHER', null, '2026-10-02T08:05:00+06:00'));
        $earlier = $this->registry()->record($company->id, $this->payload($employee, 'CARD-EARLY', null, '2026-10-01T08:00:00+06:00'));

        $this->assertSame($later->punch_identity_id, $earlier->punch_identity_id);
        $this->assertNotSame($later->punch_identity_id, $also->punch_identity_id);
        $identity = PunchIdentity::query()->find($later->punch_identity_id);
        $this->assertSame(
            '2026-10-01 02:00:00',
            $identity?->effective_from?->copy()->utc()->format('Y-m-d H:i:s'),
            (string) $identity?->getRawOriginal('effective_from'),
        );
        $this->assertSame(1, AuditEvent::query()
            ->where('action_kind', AuditEvent::ACTION_PUNCH_IDENTITY_EFFECTIVE_FROM_MOVED_EARLIER)
            ->count());
    }

    public function test_the_same_source_event_id_is_stored_twice_because_duplicate_treatment_is_not_chosen(): void
    {
        $company = $this->company('Synthetic Duplicate Punch');
        $this->signInFor($company, false);
        $employee = $this->employee($company, 'DUP-1', 'Duplicate One');

        $this->registry()->record($company->id, $this->payload($employee, 'CARD-DUP', 'SAME-SOURCE'));
        $this->registry()->record($company->id, $this->payload($employee, 'CARD-DUP', 'SAME-SOURCE'));

        $this->assertSame(2, PunchEvent::query()->where('company_id', $company->id)->where('source_event_id', 'SAME-SOURCE')->count());
    }

    public function test_an_employee_from_another_company_is_rejected(): void
    {
        $north = $this->company('Synthetic North Employee');
        $south = $this->company('Synthetic South Employee');
        $this->signInFor($north, false);
        $foreign = $this->employee($south, 'F-1', 'Foreign One');

        try {
            $this->registry()->record($north->id, $this->payload($foreign, 'CARD-FOREIGN'));
            $this->fail('A foreign employee was accepted.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'The employee must belong to the active company.',
                $exception->errors()['employee_id'][0],
            );
        }

        $this->assertSame(0, PunchEvent::query()->whereIn('company_id', [$north->id, $south->id])->count());
        $this->assertSame(0, PunchIdentity::query()->whereIn('company_id', [$north->id, $south->id])->count());
    }

    public function test_the_company_scoped_api_records_when_the_unnamed_writer_gate_is_not_applied(): void
    {
        $north = $this->company('Synthetic Api Punch');
        $south = $this->company('Synthetic Api Other');
        $user = $this->signInFor($north, false);
        CompanyMembership::query()->create([
            'user_id' => $user->id,
            'company_id' => $south->id,
            'status' => 'active',
            'can_manage_employees' => false,
            'can_manage_organization_masters' => false,
        ]);
        $employee = $this->employee($north, 'A-1', 'Api One');
        $this->withoutMiddleware(RequirePunchRecordingWriter::class);

        $created = $this->postJson('/api/punch-events', $this->payload($employee, 'CARD-API'))
            ->assertCreated()
            ->assertJsonPath('employee_id', $employee->id)
            ->assertJsonPath('identity_value', 'CARD-API')
            ->assertJsonPath('source_name', 'Gate reader')
            ->assertJsonPath('acting_user_id', $user->id)
            ->assertJsonPath('withdrawal_status', 'accepted');
        $this->assertArrayNotHasKey('work_date', $created->json());
        $this->assertArrayNotHasKey('status', $created->json());

        $this->postJson('/api/punch-events', $this->payload($employee, 'CARD-API') + [
            'company_id' => $south->id,
            'area' => 'legacy-area',
            'work_date' => '2026-10-01',
            'withdrawal_status' => 'withdrawn',
        ])->assertStatus(422)
            ->assertJsonPath('errors.company_id.0', 'The company is taken from the signed-in company context, not from the form.')
            ->assertJsonPath('errors.area.0', 'A named source is required. Area is not a source.')
            ->assertJsonPath('errors.work_date.0', 'A punch event stores an event time. It does not store a work date.')
            ->assertJsonPath('errors.withdrawal_status.0', 'A punch event cannot be withdrawn.');

        $this->withSession([CompanyContext::SESSION_KEY => $south->id]);
        $this->getJson('/api/punch-events/'.$created->json('id'))->assertNotFound();
        $this->getJson('/api/punch-events')->assertOk()->assertJsonPath('data', []);
        $this->assertSame(1, PunchEvent::query()->whereIn('company_id', [$north->id, $south->id])->count());
    }

    public function test_a_granted_writer_can_record_only_in_the_granted_company(): void
    {
        $north = $this->company('Harbor Synthetic Punch');
        $south = $this->company('Viyellatex Group');
        $user = $this->signInFor($north, false);
        CompanyMembership::query()->create([
            'user_id' => $user->id,
            'company_id' => $south->id,
            'status' => 'active',
            'can_manage_employees' => false,
            'can_manage_organization_masters' => false,
        ]);
        $employee = $this->employee($north, 'W-1', 'Writer One');
        $other = $this->employee($south, 'W-2', 'Writer Two');

        $this->artisan('hrms:authorize-punch-recording-writer', [
            '--email' => $user->email,
            '--company' => 'harbor',
        ])->expectsOutputToContain($user->email.' ('.$user->id.') in '.$north->company_name.' ('.$north->id.')')
            ->assertSuccessful();

        $this->postJson('/api/punch-events', $this->payload($employee, 'CARD-GRANTED'))
            ->assertCreated()
            ->assertJsonPath('acting_user_id', $user->id)
            ->assertJsonPath('employee_id', $employee->id);

        $this->withSession([CompanyContext::SESSION_KEY => $south->id]);
        $this->postJson('/api/punch-events', $this->payload($other, 'CARD-SOUTH'))
            ->assertForbidden()
            ->assertJsonPath('message', 'Punch recording is not authorized. No writer has been named.');
        $this->assertSame(1, PunchEvent::query()->whereIn('company_id', [$north->id, $south->id])->count());
        $this->assertFalse(
            PunchRecordingWriter::query()->where('user_id', $user->id)->where('company_id', $south->id)->exists()
        );
    }

    public function test_a_different_user_cannot_record_without_a_grant(): void
    {
        $company = $this->company('Synthetic Other Writer');
        $granted = $this->signInFor($company, false);
        $employee = $this->employee($company, 'O-1', 'Other One');
        $this->artisan('hrms:authorize-punch-recording-writer', [
            '--email' => $granted->email,
            '--company' => $company->id,
        ])->assertSuccessful();

        $this->signInFor($company, false);
        $this->postJson('/api/punch-events', $this->payload($employee, 'CARD-OTHER'))
            ->assertForbidden()
            ->assertJsonPath('message', 'Punch recording is not authorized. No writer has been named.');
        $this->assertSame(0, PunchEvent::query()->where('company_id', $company->id)->count());
    }

    public function test_inactive_membership_blocks_punch_recording(): void
    {
        $company = $this->company('Synthetic Inactive Punch');
        $user = $this->signInFor($company, true, 'inactive', true);
        $this->withSession([CompanyContext::SESSION_KEY => $company->id]);
        PunchRecordingWriter::query()->insert([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'authorized_at' => now(),
        ]);

        $this->postJson('/api/punch-events', [
            'employee_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV',
            'identity_value' => 'CARD-INACTIVE',
            'source_name' => 'Gate reader',
            'event_at' => '2026-10-01T08:00:00+06:00',
        ])->assertForbidden()
            ->assertJsonPath('message', 'This account has no active company membership.');
        $this->assertSame(0, PunchEvent::query()->where('company_id', $company->id)->count());
    }

    public function test_grant_and_revoke_stay_inside_one_company_and_require_active_membership(): void
    {
        $north = $this->company('Harbor Synthetic Punch');
        $south = $this->company('Synthetic South Grant');
        $user = User::query()->create([
            'name' => 'Synthetic Punch Writer',
            'email' => 'punch-writer-'.strtolower((string) Str::ulid()).'@example.test',
            'password' => 'synthetic-passphrase-1',
        ]);

        $this->artisan('hrms:authorize-punch-recording-writer', [
            '--email' => $user->email,
            '--company' => 'harbor',
        ])->expectsOutputToContain('does not have an active membership')
            ->assertFailed();
        $this->assertSame(0, PunchRecordingWriter::query()->where('user_id', $user->id)->count());

        CompanyMembership::query()->create([
            'user_id' => $user->id,
            'company_id' => $north->id,
            'status' => 'inactive',
            'can_manage_employees' => true,
            'can_manage_organization_masters' => true,
        ]);
        $this->artisan('hrms:authorize-punch-recording-writer', [
            '--email' => $user->email,
            '--company' => $north->id,
        ])->assertFailed();
        $this->assertSame(0, PunchRecordingWriter::query()->where('user_id', $user->id)->count());

        CompanyMembership::query()
            ->where('user_id', $user->id)
            ->where('company_id', $north->id)
            ->update(['status' => 'active']);
        CompanyMembership::query()->create([
            'user_id' => $user->id,
            'company_id' => $south->id,
            'status' => 'active',
            'can_manage_employees' => false,
            'can_manage_organization_masters' => false,
        ]);

        $this->artisan('hrms:authorize-punch-recording-writer', [
            '--email' => $user->email,
            '--company' => 'harbor',
        ])->assertSuccessful();
        $this->artisan('hrms:authorize-punch-recording-writer', [
            '--email' => $user->email,
            '--company' => $south->id,
        ])->assertSuccessful();
        $this->artisan('hrms:authorize-punch-recording-writer', [
            '--email' => $user->email,
            '--company' => 'harbor',
            '--revoke' => true,
        ])->expectsOutputToContain($north->company_name.' ('.$north->id.')')
            ->assertSuccessful();

        $this->assertFalse(
            PunchRecordingWriter::query()->where('user_id', $user->id)->where('company_id', $north->id)->exists()
        );
        $this->assertTrue(
            PunchRecordingWriter::query()->where('user_id', $user->id)->where('company_id', $south->id)->exists()
        );
    }

    /**
     * @return array{employee_id: string, identity_value: string, source_name: string, event_at: string, source_event_id?: string}
     */
    private function payload(Employee $employee, string $identity, ?string $sourceEventId = null, string $eventAt = '2026-10-01T08:00:00+06:00'): array
    {
        $payload = [
            'employee_id' => $employee->id,
            'identity_value' => $identity,
            'source_name' => 'Gate reader',
            'event_at' => $eventAt,
        ];
        if ($sourceEventId !== null) {
            $payload['source_event_id'] = $sourceEventId;
        }

        return $payload;
    }

    private function registry(): PunchRecordingRegistry
    {
        return app(PunchRecordingRegistry::class);
    }

    private function company(string $name): Company
    {
        return Company::query()->create([
            'company_name' => $name,
            'status' => 'Active',
        ]);
    }

    private function employee(Company $company, string $code, string $name): Employee
    {
        return Employee::query()->create([
            'company_id' => $company->id,
            'employee_code' => $code,
            'display_name' => $name,
        ]);
    }
}
