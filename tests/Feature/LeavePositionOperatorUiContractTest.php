<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeavePosition;
use App\Models\LeavePositionMovement;
use App\Models\LeavePositionWriter;
use App\Models\LeaveType;
use App\Models\User;
use Tests\EmployeeDatabaseTestCase;

class LeavePositionOperatorUiContractTest extends EmployeeDatabaseTestCase
{
    public function test_operator_screens_cannot_read_or_write_without_a_writer_grant(): void
    {
        $company = $this->company('Operator UI Workshop');
        $this->signInFor($company, true, 'active', true);

        foreach ([
            ['GET', '/api/leave-types'],
            ['POST', '/api/leave-types'],
            ['GET', '/api/leave-positions'],
            ['POST', '/api/leave-positions'],
        ] as [$method, $path]) {
            $this->json($method, $path, $method === 'POST' ? ['name' => 'Annual', 'status' => 'Active'] : [])
                ->assertForbidden()
                ->assertJsonPath('message', 'Leave-position records are not authorized.');
        }
    }

    public function test_operator_screens_can_list_create_rename_and_change_leave_type_status(): void
    {
        $company = $this->company('Operator UI Workshop');
        $user = $this->signInFor($company, false, 'active', false);
        $this->authorizeWriter($user, $company);

        $this->getJson('/api/leave-types')
            ->assertOk()
            ->assertJsonPath('company_name', 'Operator UI Workshop')
            ->assertJsonPath('data', []);

        $created = $this->postJson('/api/leave-types', [
            'name' => 'Casual',
            'status' => 'Active',
        ])->assertCreated()
            ->assertJsonPath('name', 'Casual')
            ->assertJsonPath('status', 'Active')
            ->assertJsonPath('company_name', 'Operator UI Workshop');

        $id = $created->json('id');
        $this->getJson('/api/leave-types/'.$id)
            ->assertOk()
            ->assertJsonPath('id', $id)
            ->assertJsonPath('name', 'Casual')
            ->assertJsonPath('status', 'Active');

        $this->putJson('/api/leave-types/'.$id, [
            'name' => 'Casual renamed',
            'status' => 'withdrawn',
        ])->assertOk()
            ->assertJsonPath('name', 'Casual renamed')
            ->assertJsonPath('status', 'withdrawn');

        $this->getJson('/api/leave-types')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Casual renamed')
            ->assertJsonPath('data.0.status', 'withdrawn');

        $this->assertSame(
            $user->id,
            AuditEvent::query()
                ->where('subject_id', $id)
                ->where('subject_kind', AuditEvent::SUBJECT_LEAVE_TYPE)
                ->value('acting_user_id'),
        );
    }

    public function test_operator_screens_record_positions_and_append_only_reversals(): void
    {
        $company = $this->company('Operator UI Workshop');
        $other = $this->company('Other Operator Workshop');
        $user = $this->signInFor($company, false, 'active', false);
        $this->authorizeWriter($user, $company);
        $employee = $this->employee($company, 'OP-1', 'Operator Person');
        $type = LeaveType::query()->create([
            'company_id' => $company->id,
            'name' => 'Operator Type',
            'status' => 'Active',
        ]);

        $created = $this->postJson('/api/leave-positions', [
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'proposed_amount' => '8.25',
            'period_label' => 'evidence-2026',
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'source_evidence' => 'Owner-supplied figure',
            'stated_basis' => 'Recorded evidence',
            'status' => 'proposed',
        ])->assertCreated()
            ->assertJsonPath('status', 'proposed')
            ->assertJsonPath('proposed_amount', '8.25')
            ->assertJsonPath('usable_as_approved_balance', false)
            ->assertJsonPath('confirmation_recorded_at', null)
            ->assertJsonPath('confirmation_actor_id', null)
            ->assertJsonPath('confirmation_decision', null);

        $positionId = $created->json('id');
        $this->assertSame($company->id, LeavePosition::query()->find($positionId)->company_id);
        $this->assertSame(0, LeavePosition::query()->where('company_id', $other->id)->count());

        $this->putJson('/api/leave-positions/'.$positionId, [
            'proposed_amount' => '8.25',
            'period_label' => 'evidence-2026',
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'source_evidence' => 'Owner-supplied figure',
            'stated_basis' => 'Recorded evidence',
            'status' => 'excepted',
        ])->assertOk()
            ->assertJsonPath('status', 'excepted')
            ->assertJsonPath('proposed_amount', '8.25')
            ->assertJsonPath('usable_as_approved_balance', false);

        $this->putJson('/api/leave-positions/'.$positionId, [
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'proposed_amount' => '8.25',
            'source_evidence' => 'Owner-supplied figure',
            'stated_basis' => 'Recorded evidence',
            'status' => 'excepted',
        ])->assertStatus(422);

        $this->getJson('/api/leave-positions')
            ->assertOk()
            ->assertJsonPath('company_name', 'Operator UI Workshop')
            ->assertJsonPath('data.0.id', $positionId)
            ->assertJsonPath('data.0.usable_as_approved_balance', false);

        $movement = $this->postJson('/api/leave-positions/'.$positionId.'/movements', [
            'amount' => '2.5',
            'direction' => 'recorded adjustment statement',
            'related_reference' => 'note-1',
        ])->assertCreated()
            ->assertJsonPath('direction', 'recorded adjustment statement')
            ->assertJsonPath('related_reference', 'note-1')
            ->assertJsonPath('acting_user_id', $user->id)
            ->assertJsonPath('usable_as_approved_balance', false);

        $originalId = $movement->json('id');
        $reversal = $this->postJson('/api/leave-positions/'.$positionId.'/movements', [
            'amount' => '2.5',
            'direction' => 'reversal of recorded adjustment statement',
            'reverses_movement_id' => $originalId,
        ])->assertCreated()
            ->assertJsonPath('reverses_movement_id', $originalId)
            ->assertJsonPath('usable_as_approved_balance', false);

        $original = LeavePositionMovement::query()->find($originalId);
        $this->assertNotNull($original);
        $this->assertSame('2.5', (string) $original->amount);
        $this->assertSame('recorded adjustment statement', $original->direction);
        $this->assertNull($original->reverses_movement_id);
        $this->assertSame($originalId, LeavePositionMovement::query()->find($reversal->json('id'))->reverses_movement_id);
        $this->assertSame(2, LeavePositionMovement::query()->where('leave_position_id', $positionId)->count());

        $this->getJson('/api/leave-positions/'.$positionId.'/movements')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('usable_as_approved_balance', false)
            ->assertJsonPath('data.0.id', $originalId)
            ->assertJsonPath('data.1.id', $reversal->json('id'));

        $this->assertSame(
            $user->id,
            AuditEvent::query()
                ->where('subject_id', $positionId)
                ->where('action_kind', AuditEvent::ACTION_LEAVE_POSITION_CREATED)
                ->value('acting_user_id'),
        );
        $this->assertSame(
            $user->id,
            AuditEvent::query()
                ->where('subject_kind', AuditEvent::SUBJECT_LEAVE_POSITION_MOVEMENT)
                ->where('subject_id', $originalId)
                ->value('acting_user_id'),
        );
    }

    public function test_operator_screens_refuse_confirmation_and_approved_balance_fields(): void
    {
        $company = $this->company('Operator UI Workshop');
        $user = $this->signInFor($company, false, 'active', false);
        $this->authorizeWriter($user, $company);
        $employee = $this->employee($company, 'OP-2', 'Operator Two');
        $type = LeaveType::query()->create([
            'company_id' => $company->id,
            'name' => 'Operator Type Two',
            'status' => 'Active',
        ]);

        $this->postJson('/api/leave-positions', [
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'proposed_amount' => '1',
            'source_evidence' => 'Evidence',
            'stated_basis' => 'Basis',
            'status' => 'proposed',
            'usable_as_approved_balance' => true,
            'confirmation_decision' => 'confirm',
        ])->assertStatus(422)
            ->assertJsonPath('errors.confirmation_decision.0', 'Confirmation cannot be recorded.');
    }

    public function test_operator_screen_sources_stay_inside_the_recording_boundary(): void
    {
        $app = $this->frontendSource('src/App.tsx');
        $links = $this->frontendSource('src/components/RealSessionLinks.tsx');
        $types = $this->frontendSource('src/pages/RealSessionLeaveTypesPage.tsx');
        $positions = $this->frontendSource('src/pages/RealSessionLeavePositionsPage.tsx');
        $client = $this->frontendSource('src/lib/leavePositionRecordingApi.ts');

        $this->assertStringContainsString('path="/session/leave-types"', $app);
        $this->assertStringContainsString('path="/session/leave-types/:recordId"', $app);
        $this->assertStringContainsString('path="/session/leave-positions"', $app);
        $this->assertStringContainsString('path="/session/leave-positions/:recordId"', $app);
        $this->assertStringContainsString('to="/session/leave-types"', $links);
        $this->assertStringContainsString('to="/session/leave-positions"', $links);

        $this->assertStringContainsString('Only name and status are recorded.', $types);
        $this->assertStringContainsString('Rename or change status', $types);
        $this->assertStringContainsString('value="Active"', $types);
        $this->assertStringContainsString('value="withdrawn"', $types);
        $this->assertStringNotContainsString('paid', strtolower($types));

        $this->assertStringContainsString('value="proposed"', $positions);
        $this->assertStringContainsString('value="excepted"', $positions);
        $this->assertStringContainsString('Not usable', $positions);
        $this->assertStringContainsString('A reversal adds a new row.', $positions);
        $this->assertStringContainsString('The selected movement is not edited.', $positions);
        $this->assertStringNotContainsString('method: \'DELETE\'', $positions);
        $this->assertStringNotContainsString('method: \'PATCH\'', $positions);

        $this->assertStringContainsString('value.usable_as_approved_balance !== false', $client);
        $this->assertStringContainsString('value.confirmation_decision !== null', $client);
        $this->assertStringContainsString("'/api/leave-types'", $client);
        $this->assertStringContainsString("'/api/leave-positions'", $client);
        $this->assertStringContainsString('/movements', $client);
        $this->assertStringContainsString('createLeaveType', $types);
        $this->assertStringContainsString('updateLeaveType', $types);
        $this->assertStringContainsString('fetchLeaveType', $types);
        $this->assertStringContainsString('Open Laravel login', $types);
        $this->assertStringContainsString('Try again', $types);
        $this->assertStringContainsString('role="alert"', $types);
        $this->assertStringContainsString('createLeavePosition', $positions);
        $this->assertStringContainsString('updateLeavePosition', $positions);
        $this->assertStringContainsString('fetchLeavePosition', $positions);
        $this->assertStringContainsString('fetchLeaveMovements', $positions);
        $this->assertStringContainsString('createLeaveMovement', $positions);
        $this->assertStringContainsString('fetchEmployeeDirectory', $positions);
        $this->assertStringContainsString('Select employee', $positions);
        $this->assertStringContainsString('Update recorded position', $positions);
        $this->assertStringContainsString('Record a movement', $positions);
        $this->assertStringContainsString('movement.acting_user_id', $positions);
        $this->assertStringContainsString('Usable as approved balance', $positions);
        $this->assertStringContainsString('Open Laravel login', $positions);
        $this->assertStringContainsString('Try again', $positions);
        $this->assertStringContainsString('role="alert"', $positions);
        $this->assertStringNotContainsString('placeholder="Employee id"', $positions);
        $this->assertStringNotContainsString('can_manage_organization_masters', $types.$positions);
        $this->assertStringNotContainsString('leave_position_writers', $client.$types.$positions.$links.$app);
        $this->assertStringNotContainsString('confirmation_decision', $types.$positions);
        $this->assertStringNotContainsString('accrual', strtolower($client.$types.$positions));
        $this->assertStringNotContainsString('payroll', strtolower($client.$types.$positions));
        $this->assertStringNotContainsString('booking', strtolower($client.$types.$positions));
    }

    private function frontendSource(string $path): string
    {
        $file = base_path('frontend/'.$path);
        $this->assertFileExists($file);

        return (string) file_get_contents($file);
    }

    private function authorizeWriter(User $user, Company $company): void
    {
        LeavePositionWriter::query()->insert([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'authorized_at' => now(),
        ]);
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
