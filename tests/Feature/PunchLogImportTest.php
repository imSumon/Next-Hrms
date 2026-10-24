<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PunchEvent;
use App\Models\PunchRecordingWriter;
use Tests\EmployeeDatabaseTestCase;

class PunchLogImportTest extends EmployeeDatabaseTestCase
{
    public function test_preview_reports_every_row_and_stores_nothing(): void
    {
        $company = $this->writerCompany('Harbor Log Preview');
        $this->employee($company, 'SYN-LOG-1');

        $response = $this->postJson('/api/punch-events/imports', [
            'source_name' => 'synthetic device export',
            'mode' => 'preview',
            'rows' => [
                ['employee_code' => 'SYN-LOG-1', 'identity_value' => 'CARD-1', 'event_at' => '2026-09-02T08:10:00+06:00', 'source_event_id' => 'dev-1'],
                ['employee_code' => 'SYN-LOG-1', 'identity_value' => 'CARD-1', 'event_at' => '2026-09-02T17:40:00+06:00', 'source_event_id' => 'dev-2'],
            ],
        ])->assertOk();

        $this->assertFalse($response->json('committed'));
        $this->assertSame(2, $response->json('accepted_count'));
        $this->assertSame(['accepted', 'accepted'], array_column($response->json('rows'), 'outcome'));
        $this->assertSame(0, PunchEvent::query()->where('company_id', $company->id)->count());
    }

    public function test_one_bad_or_duplicate_row_blocks_the_whole_batch_and_a_clean_retry_commits(): void
    {
        $company = $this->writerCompany('Harbor Log Commit');
        $this->employee($company, 'SYN-LOG-2');
        $rows = [
            ['employee_code' => 'SYN-LOG-2', 'identity_value' => 'CARD-2', 'event_at' => '2026-09-02T08:10:00+06:00', 'source_event_id' => 'dev-10'],
            ['employee_code' => 'SYN-LOG-2', 'identity_value' => 'CARD-2', 'event_at' => '2026-09-02T08:10:00+06:00', 'source_event_id' => 'dev-10'],
            ['employee_code' => 'NO-SUCH-CODE', 'identity_value' => 'CARD-9', 'event_at' => '2026-09-02T09:00:00+06:00', 'source_event_id' => 'dev-11'],
            ['employee_code' => 'SYN-LOG-2', 'identity_value' => 'CARD-2', 'event_at' => '2026-09-02 17:40:00', 'source_event_id' => 'dev-12'],
        ];

        $failed = $this->postJson('/api/punch-events/imports', ['source_name' => 'synthetic device export', 'mode' => 'commit', 'rows' => $rows])
            ->assertStatus(422);

        $this->assertFalse($failed->json('committed'));
        $this->assertSame(['accepted', 'duplicate', 'rejected', 'rejected'], array_column($failed->json('rows'), 'outcome'));
        $this->assertSame('No employee in this company has this code.', $failed->json('rows.2.message'));
        $this->assertSame('The event time needs a date, a time, and a UTC offset.', $failed->json('rows.3.message'));
        $this->assertSame(0, PunchEvent::query()->where('company_id', $company->id)->count());

        $clean = [$rows[0], ['employee_code' => 'SYN-LOG-2', 'identity_value' => 'CARD-2', 'event_at' => '2026-09-02T17:40:00+06:00', 'source_event_id' => 'dev-12']];
        $stored = $this->postJson('/api/punch-events/imports', ['source_name' => 'synthetic device export', 'mode' => 'commit', 'rows' => $clean])
            ->assertCreated();
        $this->assertTrue($stored->json('committed'));
        $events = PunchEvent::query()->where('company_id', $company->id)->orderBy('event_at')->get();
        $this->assertCount(2, $events);
        $this->assertSame('synthetic device export', $events[0]->source_name);
        $this->assertSame('dev-10', $events[0]->source_event_id);
        $this->assertSame('2026-09-02 08:10:00', $events[0]->event_at->timezone('Asia/Dhaka')->format('Y-m-d H:i:s'));
        $this->assertNotNull($events[0]->ingested_at);

        $repeat = $this->postJson('/api/punch-events/imports', ['source_name' => 'synthetic device export', 'mode' => 'commit', 'rows' => $clean])
            ->assertStatus(422);
        $this->assertSame(2, $repeat->json('duplicate_count'));
        $this->assertSame(2, PunchEvent::query()->where('company_id', $company->id)->count());
    }

    public function test_an_employee_code_from_another_company_is_rejected_and_the_route_needs_the_writer_grant(): void
    {
        $other = $this->company('Harbor Log Other');
        $this->employee($other, 'SYN-LOG-FOREIGN');
        $company = $this->writerCompany('Harbor Log Own');

        $this->postJson('/api/punch-events/imports', [
            'source_name' => 'synthetic device export',
            'mode' => 'commit',
            'rows' => [['employee_code' => 'SYN-LOG-FOREIGN', 'identity_value' => 'CARD-F', 'event_at' => '2026-09-02T08:10:00+06:00']],
        ])->assertStatus(422)->assertJsonPath('rows.0.outcome', 'rejected');
        $this->assertSame(0, PunchEvent::query()->whereIn('company_id', [$company->id, $other->id])->count());

        $ungranted = $this->company('Harbor Log Ungranted');
        $this->signInFor($ungranted, false);
        $this->postJson('/api/punch-events/imports', ['source_name' => 'x', 'mode' => 'preview', 'rows' => []])->assertForbidden();
    }

    private function writerCompany(string $name): Company
    {
        $company = $this->company($name);
        $user = $this->signInFor($company, false);
        PunchRecordingWriter::query()->insert([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'authorized_at' => now(),
        ]);

        return $company;
    }

    private function company(string $name): Company
    {
        return Company::query()->create([
            'company_name' => $name,
            'status' => 'Active',
        ]);
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
