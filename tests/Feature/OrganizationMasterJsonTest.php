<?php

namespace Tests\Feature;

use App\Audit\EmployeeAuditTrail;
use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use App\Organization\MasterRecordRegistry;
use App\Tenancy\CompanyContext;
use Illuminate\Support\Str;
use Tests\EmployeeDatabaseTestCase;

class OrganizationMasterJsonTest extends EmployeeDatabaseTestCase
{
    public function test_a_reader_sees_only_the_active_company_and_cannot_write(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        $this->signInFor($north, true, 'active', false);

        foreach ($this->masters() as $master) {
            $this->postJson('/api/'.$master['path'], [
                'name' => 'North Office',
                'code' => 'NO',
                'status' => 'Active',
            ])->assertForbidden()
                ->assertJsonPath('message', 'Organization master changes are not authorized.');

            $this->signInFor($north, false, 'active', true);
            $this->postJson('/api/'.$master['path'].'?company_id='.$south->id, [
                'name' => 'North Office',
                'code' => 'NO',
                'status' => 'Active',
            ])->assertUnprocessable()
                ->assertJsonPath('errors.company_id.0', 'The company is taken from the signed-in company context, not from the form.');
            $this->assertSame(0, $master['model']::query()->where('company_id', $south->id)->count());

            $created = $this->postJson('/api/'.$master['path'], [
                'name' => 'North Office',
                'code' => 'NO',
                'status' => 'Active',
            ])->assertCreated()
                ->assertJsonPath('name', 'North Office')
                ->assertJsonPath('code', 'NO')
                ->assertJsonPath('status', 'Active')
                ->assertJsonPath('company_name', 'Synthetic North Workshop')
                ->assertJsonPath('can_manage_organization_masters', true)
                ->assertJsonPath('changes', []);

            $this->assertSame($north->id, $master['model']::query()->find($created->json('id'))->company_id);
            $this->putJson('/api/employees/'.strtoupper((string) Str::ulid()), [
                'display_name' => 'Changed By Master Grant',
            ])->assertForbidden()
                ->assertJsonPath('message', 'Employee changes are not authorized.');

            $southRow = $master['model']::query()->create([
                'company_id' => $south->id,
                'code' => 'NO',
                'name' => 'South Office',
                'status' => 'Active',
            ]);

            $this->signInFor($north, false, 'active', false);
            $this->getJson('/api/'.$master['path'].'?company_id='.$south->id)
                ->assertOk()
                ->assertJsonPath('company_name', 'Synthetic North Workshop')
                ->assertJsonPath('can_manage_organization_masters', false)
                ->assertJsonPath('per_page', MasterRecordRegistry::PER_PAGE)
                ->assertJsonPath('data.0.name', 'North Office')
                ->assertJsonMissing(['name' => 'South Office']);

            $this->getJson('/api/'.$master['path'].'/'.$southRow->id.'?company_id='.$south->id)
                ->assertNotFound()
                ->assertJsonMissing(['name' => 'South Office']);

            $this->getJson('/api/'.$master['path'].'/'.strtoupper((string) Str::ulid()))
                ->assertNotFound();
        }
    }

    public function test_codes_are_optional_unique_per_company_and_master(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        $this->signInFor($north, false, 'active', true);

        foreach ($this->masters() as $master) {
            $this->postJson('/api/'.$master['path'], [
                'name' => 'First Blank',
                'code' => '   ',
                'status' => 'Active',
            ])->assertCreated()->assertJsonPath('code', null);

            $this->postJson('/api/'.$master['path'], [
                'name' => 'Second Blank',
                'status' => 'Active',
            ])->assertCreated()->assertJsonPath('code', null);

            $this->postJson('/api/'.$master['path'], [
                'name' => 'Coded',
                'code' => 'SHARED',
                'status' => 'Active',
            ])->assertCreated();

            $this->postJson('/api/'.$master['path'], [
                'name' => 'Again',
                'code' => 'SHARED',
                'status' => 'Active',
            ])->assertStatus(422)
                ->assertJsonPath('errors.code.0', 'This code is already used in this company.');

            $this->assertSame(1, $master['model']::query()->where('company_id', $north->id)->where('code', 'SHARED')->count());
        }

        $this->assertSame(1, Department::query()->where('code', 'SHARED')->count());
        $this->assertSame(1, Designation::query()->where('code', 'SHARED')->count());

        $this->signInFor($south, false, 'active', true);
        $this->postJson('/api/departments', [
            'name' => 'South Shared',
            'code' => 'SHARED',
            'status' => 'Active',
        ])->assertCreated();
    }

    public function test_validation_rejects_a_blank_name_a_bad_status_and_an_identity_change(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $this->signInFor($north, false, 'active', true);

        foreach ($this->masters() as $master) {
            $this->postJson('/api/'.$master['path'], [
                'name' => '   ',
                'status' => 'Active',
            ])->assertStatus(422)->assertJsonPath('errors.name.0', 'Name is required.');

            $this->postJson('/api/'.$master['path'], [
                'name' => 'Needs Status',
            ])->assertStatus(422)->assertJsonPath('errors.status.0', 'Status must be Active or withdrawn.');

            $this->postJson('/api/'.$master['path'], [
                'name' => 'Bad Status',
                'status' => 'closed',
            ])->assertStatus(422);

            $this->postJson('/api/'.$master['path'], [
                'name' => 'With Company',
                'status' => 'Active',
                'company_id' => $north->id,
            ])->assertStatus(422);

            $created = $this->postJson('/api/'.$master['path'], [
                'name' => 'Kept Code',
                'code' => 'KEEP',
                'status' => 'Active',
            ])->assertCreated();

            $this->putJson('/api/'.$master['path'].'/'.$created->json('id'), [
                'name' => 'Renamed',
                'status' => 'Active',
                'code' => 'OTHER',
            ])->assertStatus(422)->assertJsonPath('errors.code.0', 'The code cannot be changed.');

            $row = $master['model']::query()->find($created->json('id'));
            $this->assertSame('Kept Code', $row->name);
            $this->assertSame('KEEP', $row->code);
            $this->assertSame(0, AuditEvent::query()->where('subject_id', $row->id)->count());
        }
    }

    public function test_withdrawal_keeps_the_row_and_audits_name_and_status(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $user = $this->signInFor($north, false, 'active', true);

        foreach ($this->masters() as $master) {
            $created = $this->postJson('/api/'.$master['path'], [
                'name' => 'Open Desk',
                'code' => 'OD',
                'status' => 'Active',
            ])->assertCreated();
            $id = $created->json('id');

            $updated = $this->putJson('/api/'.$master['path'].'/'.$id, [
                'name' => 'Closed Desk',
                'status' => 'withdrawn',
            ])->assertOk()
                ->assertJsonPath('id', $id)
                ->assertJsonPath('code', 'OD')
                ->assertJsonPath('status', 'withdrawn')
                ->assertJsonPath('changes.0.previous_name', 'Open Desk')
                ->assertJsonPath('changes.0.new_name', 'Closed Desk')
                ->assertJsonPath('changes.0.actor_name', $user->name)
                ->assertJsonPath('changes.1.previous_status', 'Active')
                ->assertJsonPath('changes.1.new_status', 'withdrawn')
                ->assertJsonCount(2, 'changes');

            $events = AuditEvent::query()->where('subject_id', $id)->orderBy('id')->get();
            $this->assertSame($updated->json('changes.0.acted_at'), $events[0]->acted_at->format(\DateTimeInterface::ATOM));
            $this->assertSame($north->id, $events[0]->company_id);
            $this->assertSame($master['subject'], $events[0]->subject_kind);
            $this->assertSame($user->id, $events[0]->acting_user_id);

            $this->getJson('/api/'.$master['path'].'/'.$id)
                ->assertOk()
                ->assertJsonPath('status', 'withdrawn')
                ->assertJsonPath('name', 'Closed Desk')
                ->assertJsonCount(2, 'changes');

            $this->putJson('/api/'.$master['path'].'/'.$id, [
                'name' => 'Closed Desk',
                'status' => 'Active',
            ])->assertOk()->assertJsonPath('status', 'Active');
        }
    }

    public function test_an_audit_failure_rolls_back_the_master_change(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $this->signInFor($north, false, 'active', true);
        $created = $this->postJson('/api/departments', [
            'name' => 'Open Desk',
            'status' => 'Active',
        ])->assertCreated();

        $this->mock(EmployeeAuditTrail::class, function ($mock): void {
            $mock->shouldReceive('record')->once()->andThrow(new \RuntimeException('audit failed'));
        });

        $this->putJson('/api/departments/'.$created->json('id'), [
            'name' => 'Closed Desk',
            'status' => 'withdrawn',
        ])->assertStatus(500)
            ->assertJsonMissing(['name' => 'Closed Desk']);

        $row = Department::query()->find($created->json('id'));
        $this->assertSame('Open Desk', $row->name);
        $this->assertSame('Active', $row->status);
        $this->assertSame(0, AuditEvent::query()->where('company_id', $north->id)->count());
    }

    public function test_guests_and_missing_company_context_are_rejected(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        Department::query()->create([
            'company_id' => $north->id,
            'name' => 'North Office',
            'status' => 'Active',
        ]);

        $this->getJson('/api/departments')
            ->assertUnauthorized()
            ->assertJsonMissing(['name' => 'North Office']);

        $user = User::query()->create([
            'name' => 'Synthetic Clerk',
            'email' => 'clerk-'.strtolower((string) Str::ulid()).'@example.test',
            'password' => 'synthetic-passphrase-1',
        ]);
        $this->actingAs($user);

        $this->getJson('/api/departments')
            ->assertForbidden()
            ->assertJsonPath('message', 'This account has no active company membership.')
            ->assertJsonMissing(['name' => 'North Office']);

        foreach ([$north, $south] as $company) {
            CompanyMembership::query()->create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'status' => 'active',
                'can_manage_employees' => false,
                'can_manage_organization_masters' => true,
            ]);
        }

        $this->flushSession();
        $this->actingAs($user);

        $this->getJson('/api/departments')
            ->assertForbidden()
            ->assertJsonPath('message', 'An active company must be selected.')
            ->assertJsonMissing(['name' => 'North Office']);
        $this->assertNull(session(CompanyContext::SESSION_KEY));
    }

    /**
     * @return list<array{path: string, model: class-string, subject: string}>
     */
    private function masters(): array
    {
        return [
            ['path' => 'departments', 'model' => Department::class, 'subject' => AuditEvent::SUBJECT_DEPARTMENT],
            ['path' => 'designations', 'model' => Designation::class, 'subject' => AuditEvent::SUBJECT_DESIGNATION],
        ];
    }

    private function company(string $name): Company
    {
        return Company::query()->create([
            'company_name' => $name,
            'status' => 'Active',
        ]);
    }
}
