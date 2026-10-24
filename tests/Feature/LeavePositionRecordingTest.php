<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Employee;
use App\Models\LeavePosition;
use App\Models\LeavePositionMovement;
use App\Models\LeavePositionWriter;
use App\Models\LeaveType;
use App\Models\User;
use App\Tenancy\CompanyContext;
use Illuminate\Support\Str;
use Tests\EmployeeDatabaseTestCase;

class LeavePositionRecordingTest extends EmployeeDatabaseTestCase
{
    public function test_membership_flags_do_not_grant_leave_access(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $this->signInFor($north, true, 'active', true);

        $this->getJson('/api/leave-types')
            ->assertForbidden()
            ->assertJsonPath('message', 'Leave-position records are not authorized.');
        $this->postJson('/api/leave-types', [
            'name' => 'Annual',
            'status' => 'Active',
        ])->assertForbidden();
        $this->assertSame(0, LeaveType::query()->count());
    }

    public function test_an_owner_named_writer_can_record_types_positions_and_movements(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        $user = $this->signInFor($north, false, 'active', false);
        $this->authorizeWriter($user, $north);
        $employee = $this->employee($north, 'N-1', 'North Person');
        $southEmployee = $this->employee($south, 'S-1', 'South Person');

        $this->getJson('/api/leave-types')
            ->assertOk()
            ->assertJsonPath('company_name', 'Synthetic North Workshop')
            ->assertJsonPath('data', []);

        $type = $this->postJson('/api/leave-types', [
            'name' => 'Recorded Type',
            'status' => 'Active',
            'company_id' => $south->id,
        ])->assertStatus(422)
            ->assertJsonPath('errors.company_id.0', 'The company is taken from the signed-in company context, not from the form.');

        $type = $this->postJson('/api/leave-types', [
            'name' => 'Recorded Type',
            'status' => 'Active',
        ])->assertCreated()
            ->assertJsonPath('name', 'Recorded Type')
            ->assertJsonPath('status', 'Active')
            ->assertJsonPath('company_name', 'Synthetic North Workshop');

        $this->assertSame($north->id, LeaveType::query()->find($type->json('id'))->company_id);

        $this->postJson('/api/leave-positions', [
            'employee_id' => $southEmployee->id,
            'leave_type_id' => $type->json('id'),
            'proposed_amount' => '10',
            'source_evidence' => 'Owner-supplied figure',
            'stated_basis' => 'Recorded evidence',
            'status' => 'proposed',
        ])->assertStatus(422)
            ->assertJsonPath('errors.employee_id.0', 'The employee must belong to the active company.');

        $created = $this->postJson('/api/leave-positions', [
            'employee_id' => $employee->id,
            'leave_type_id' => $type->json('id'),
            'proposed_amount' => '12.5',
            'period_label' => 'evidence-2026',
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'source_evidence' => 'Owner-supplied figure',
            'stated_basis' => 'Recorded evidence',
            'status' => 'proposed',
        ])->assertCreated()
            ->assertJsonPath('status', 'proposed')
            ->assertJsonPath('proposed_amount', '12.5')
            ->assertJsonPath('period_label', 'evidence-2026')
            ->assertJsonPath('usable_as_approved_balance', false)
            ->assertJsonPath('confirmation_recorded_at', null)
            ->assertJsonPath('confirmation_decision', null);

        $positionId = $created->json('id');
        $this->assertSame($north->id, LeavePosition::query()->find($positionId)->company_id);

        $this->putJson('/api/leave-positions/'.$positionId, [
            'proposed_amount' => '12.5',
            'period_label' => 'evidence-2026',
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'source_evidence' => 'Owner-supplied figure',
            'stated_basis' => 'Recorded evidence',
            'status' => 'excepted',
        ])->assertOk()
            ->assertJsonPath('status', 'excepted')
            ->assertJsonPath('usable_as_approved_balance', false);

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
                ->where('subject_id', $positionId)
                ->where('action_kind', AuditEvent::ACTION_LEAVE_POSITION_UPDATED)
                ->value('acting_user_id'),
        );

        $movement = $this->postJson('/api/leave-positions/'.$positionId.'/movements', [
            'amount' => '2',
            'direction' => 'recorded adjustment statement',
        ])->assertCreated()
            ->assertJsonPath('direction', 'recorded adjustment statement')
            ->assertJsonPath('acting_user_id', $user->id)
            ->assertJsonPath('usable_as_approved_balance', false);

        $reversal = $this->postJson('/api/leave-positions/'.$positionId.'/movements', [
            'amount' => '2',
            'direction' => 'reversal of recorded adjustment statement',
            'reverses_movement_id' => $movement->json('id'),
        ])->assertCreated()
            ->assertJsonPath('reverses_movement_id', $movement->json('id'));

        $original = LeavePositionMovement::query()->find($movement->json('id'));
        $this->assertNotNull($original);
        $this->assertSame('2', (string) $original->amount);
        $this->assertSame('recorded adjustment statement', $original->direction);
        $this->assertSame($user->id, $original->acting_user_id);
        $this->assertNull($original->reverses_movement_id);
        $this->assertSame($movement->json('id'), LeavePositionMovement::query()->find($reversal->json('id'))->reverses_movement_id);
        $this->assertSame(2, LeavePositionMovement::query()->where('leave_position_id', $positionId)->count());

        $this->getJson('/api/leave-positions/'.$positionId.'/movements')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('usable_as_approved_balance', false)
            ->assertJsonPath('data.0.id', $movement->json('id'));
    }

    public function test_confirmation_and_cross_company_reads_are_refused(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        $northUser = $this->signInFor($north, false, 'active', false);
        $this->authorizeWriter($northUser, $north);
        $employee = $this->employee($north, 'N-2', 'North Two');
        $type = LeaveType::query()->create([
            'company_id' => $north->id,
            'name' => 'North Type',
            'status' => 'Active',
        ]);

        $this->postJson('/api/leave-positions', [
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'proposed_amount' => '1',
            'source_evidence' => 'Evidence',
            'stated_basis' => 'Basis',
            'status' => 'confirmed',
        ])->assertStatus(422)->assertJsonPath('errors.status.0', 'Status must be proposed or excepted.');

        $this->postJson('/api/leave-positions', [
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'proposed_amount' => '1',
            'source_evidence' => 'Evidence',
            'stated_basis' => 'Basis',
            'status' => 'proposed',
            'confirmation_decision' => 'confirm',
        ])->assertStatus(422)->assertJsonPath('errors.confirmation_decision.0', 'Confirmation cannot be recorded.');

        $created = $this->postJson('/api/leave-positions', [
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'proposed_amount' => '1',
            'source_evidence' => 'Evidence',
            'stated_basis' => 'Basis',
            'status' => 'proposed',
        ])->assertCreated();

        $southUser = $this->signInFor($south, true, 'active', true);
        $this->authorizeWriter($southUser, $south);
        $this->getJson('/api/leave-positions/'.$created->json('id').'?company_id='.$north->id)
            ->assertNotFound()
            ->assertJsonMissing(['usable_as_approved_balance' => true]);
        $this->assertSame(0, LeavePosition::query()->where('company_id', $south->id)->count());
        $this->getJson('/api/leave-types')
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonMissing(['name' => 'North Type']);
    }

    public function test_guests_and_unselected_company_cannot_read_leave_records(): void
    {
        $north = $this->company('Synthetic North Workshop');
        LeaveType::query()->create([
            'company_id' => $north->id,
            'name' => 'Hidden Type',
            'status' => 'Active',
        ]);

        $this->getJson('/api/leave-types')
            ->assertUnauthorized()
            ->assertJsonMissing(['name' => 'Hidden Type']);

        $user = User::query()->create([
            'name' => 'Synthetic Clerk',
            'email' => 'clerk-'.strtolower((string) Str::ulid()).'@example.test',
            'password' => 'synthetic-passphrase-1',
        ]);
        $this->actingAs($user);
        $this->getJson('/api/leave-types')
            ->assertForbidden()
            ->assertJsonPath('message', 'This account has no active company membership.');
        $this->assertNull(session(CompanyContext::SESSION_KEY));
    }

    public function test_a_writer_grant_does_not_authorize_another_company_membership(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        $user = $this->signInFor($north, true, 'active', true);
        CompanyMembership::query()->create([
            'user_id' => $user->id,
            'company_id' => $south->id,
            'status' => 'active',
            'can_manage_employees' => true,
            'can_manage_organization_masters' => true,
        ]);
        $this->authorizeWriter($user, $north);
        LeaveType::query()->create([
            'company_id' => $south->id,
            'name' => 'South Hidden Type',
            'status' => 'Active',
        ]);

        $this->withSession([CompanyContext::SESSION_KEY => $south->id])
            ->getJson('/api/leave-types')
            ->assertForbidden()
            ->assertJsonPath('message', 'Leave-position records are not authorized.')
            ->assertJsonMissing(['name' => 'South Hidden Type']);

        $this->withSession([CompanyContext::SESSION_KEY => $north->id])
            ->getJson('/api/leave-types')
            ->assertOk()
            ->assertJsonPath('company_name', 'Synthetic North Workshop')
            ->assertJsonPath('data', []);
    }

    public function test_grant_and_revoke_commands_change_only_the_named_company(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        $user = User::query()->create([
            'name' => 'Synthetic Clerk',
            'email' => 'writer-'.strtolower((string) Str::ulid()).'@example.test',
            'password' => 'synthetic-passphrase-1',
        ]);

        $this->artisan('hrms:authorize-leave-position-writer', [
            '--email' => $user->email,
            '--company' => $north->id,
        ])->expectsOutputToContain($north->company_name.' ('.$north->id.')')
            ->assertSuccessful();
        $this->assertTrue(
            LeavePositionWriter::query()->where('user_id', $user->id)->where('company_id', $north->id)->exists()
        );
        $this->assertFalse(
            LeavePositionWriter::query()->where('user_id', $user->id)->where('company_id', $south->id)->exists()
        );

        LeavePositionWriter::query()->insert([
            'company_id' => $south->id,
            'user_id' => $user->id,
            'authorized_at' => now(),
        ]);

        $this->artisan('hrms:authorize-leave-position-writer', [
            '--email' => $user->email,
            '--company' => $north->id,
            '--revoke' => true,
        ])->expectsOutputToContain($north->company_name.' ('.$north->id.')')
            ->assertSuccessful();
        $this->assertFalse(
            LeavePositionWriter::query()->where('user_id', $user->id)->where('company_id', $north->id)->exists()
        );
        $this->assertTrue(
            LeavePositionWriter::query()->where('user_id', $user->id)->where('company_id', $south->id)->exists()
        );
    }

    public function test_updates_and_foreign_reversals_cannot_confirm_or_rewrite_history(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        $user = $this->signInFor($north, false, 'active', false);
        $this->authorizeWriter($user, $north);
        $employee = $this->employee($north, 'N-3', 'North Three');
        $type = LeaveType::query()->create([
            'company_id' => $north->id,
            'name' => 'North Type Three',
            'status' => 'Active',
        ]);
        $position = $this->postJson('/api/leave-positions', [
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'proposed_amount' => '3',
            'source_evidence' => 'Evidence',
            'stated_basis' => 'Basis',
            'status' => 'proposed',
        ])->assertCreated();

        $this->putJson('/api/leave-positions/'.$position->json('id'), [
            'proposed_amount' => '3',
            'source_evidence' => 'Evidence',
            'stated_basis' => 'Basis',
            'status' => 'proposed',
            'confirmation_decision' => 'confirm',
        ])->assertStatus(422)->assertJsonPath('errors.confirmation_decision.0', 'Confirmation cannot be recorded.');

        $this->assertNull(LeavePosition::query()->find($position->json('id'))->confirmation_decision);

        $first = $this->postJson('/api/leave-positions/'.$position->json('id').'/movements', [
            'amount' => '1',
            'direction' => 'first recorded statement',
        ])->assertCreated();

        $otherPosition = LeavePosition::query()->create([
            'company_id' => $north->id,
            'employee_id' => $this->employee($north, 'N-4', 'North Four')->id,
            'leave_type_id' => $type->id,
            'proposed_amount' => '4',
            'source_evidence' => 'Other evidence',
            'stated_basis' => 'Other basis',
            'status' => 'proposed',
        ]);
        $foreignMovement = LeavePositionMovement::query()->create([
            'company_id' => $north->id,
            'leave_position_id' => $otherPosition->id,
            'amount' => '9',
            'direction' => 'other position statement',
            'acting_user_id' => $user->id,
            'recorded_at' => now(),
        ]);

        $this->postJson('/api/leave-positions/'.$position->json('id').'/movements', [
            'amount' => '1',
            'direction' => 'bad reversal',
            'reverses_movement_id' => $foreignMovement->id,
        ])->assertStatus(422)->assertJsonPath('errors.reverses_movement_id.0', 'The reversed movement must belong to this position.');

        $this->assertSame('1', (string) LeavePositionMovement::query()->find($first->json('id'))->amount);
        $this->assertSame(0, LeavePosition::query()->where('company_id', $south->id)->count());
        $this->assertFalse($position->json('usable_as_approved_balance'));
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
