<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Employee;
use App\Models\EmploymentEpisode;
use App\Models\User;
use App\Tenancy\CompanyContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Auth;
use Tests\EmployeeDatabaseTestCase;

class AuthenticationAndMembershipTest extends EmployeeDatabaseTestCase
{
    public function test_valid_login_regenerates_the_session_and_invalid_login_is_generic(): void
    {
        $company = Company::query()->create([
            'company_name' => 'Synthetic North Workshop',
            'status' => 'Active',
        ]);
        $user = User::query()->create([
            'name' => 'Synthetic Clerk',
            'email' => 'clerk@example.test',
            'password' => 'synthetic-passphrase-1',
        ]);
        CompanyMembership::query()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'status' => 'active',
            'can_manage_employees' => false,
        ]);

        $this->get('/login')->assertOk()->assertSee('name="_token"', false);

        $before = session()->getId();
        $this->post('/login', [
            'email' => 'CLERK@example.test',
            'password' => 'synthetic-passphrase-1',
        ])->assertRedirect('/employees');
        $this->assertNotSame($before, session()->getId());
        $this->assertAuthenticatedAs($user);

        Auth::logout();
        $this->post('/login', [
            'email' => 'clerk@example.test',
            'password' => 'not-the-passphrase',
        ])->assertSessionHasErrors([
            'email' => 'Those credentials are not valid.',
        ]);
        $this->post('/login', [
            'email' => 'missing@example.test',
            'password' => 'synthetic-passphrase-1',
        ])->assertSessionHasErrors([
            'email' => 'Those credentials are not valid.',
        ]);
    }

    public function test_logout_ends_the_session(): void
    {
        $company = Company::query()->create([
            'company_name' => 'Synthetic North Workshop',
            'status' => 'Active',
        ]);
        $employee = Employee::query()->create([
            'company_id' => $company->id,
            'employee_code' => 'SYN-1',
            'display_name' => 'North Ada Sample',
        ]);
        $this->signInFor($company);

        $sessionId = session()->getId();
        $token = session()->token();

        $this->post('/logout')
            ->assertRedirect('/login')
            ->assertSessionHas('status', 'You have signed out.');
        $this->assertGuest();
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertNotSame($token, session()->token());
        $this->assertNull(session(CompanyContext::SESSION_KEY));

        $this->get('/login')->assertOk()->assertSee('You have signed out.');
        $this->get('/employees')->assertRedirect('/login');
        $this->getJson('/api/employees')
            ->assertUnauthorized()
            ->assertJsonMissing(['display_name' => 'North Ada Sample'])
            ->assertJsonMissing(['company_name' => 'Synthetic North Workshop']);
        $this->getJson('/api/employees/'.$employee->id)
            ->assertUnauthorized()
            ->assertJsonMissing(['display_name' => 'North Ada Sample']);
    }

    public function test_login_post_stays_on_the_web_middleware_stack(): void
    {
        $route = app('router')->getRoutes()->getByName('login.store');

        $this->assertContains('web', $route->gatherMiddleware());
        $this->assertContains(
            PreventRequestForgery::class,
            app(Middleware::class)->getMiddlewareGroups()['web'],
        );
    }

    public function test_authenticated_user_without_membership_is_denied(): void
    {
        $user = User::query()->create([
            'name' => 'Synthetic Clerk',
            'email' => 'alone@example.test',
            'password' => 'synthetic-passphrase-1',
        ]);
        $this->actingAs($user);

        $this->get('/employees')
            ->assertForbidden()
            ->assertSee('no active company membership');
    }

    public function test_inactive_membership_is_denied(): void
    {
        $company = Company::query()->create([
            'company_name' => 'Synthetic North Workshop',
            'status' => 'Active',
        ]);
        $this->signInFor($company, true, 'inactive');

        $this->get('/employees')
            ->assertForbidden()
            ->assertSee('no active company membership');
    }

    public function test_view_is_allowed_and_changes_require_the_module_grant(): void
    {
        $company = Company::query()->create([
            'company_name' => 'Synthetic North Workshop',
            'status' => 'Active',
        ]);
        Employee::query()->create([
            'company_id' => $company->id,
            'employee_code' => 'SYN-1',
            'display_name' => 'North Ada Sample',
        ]);
        $this->signInFor($company, false);

        $this->get('/employees')->assertOk()->assertSee('North Ada Sample')->assertDontSee('Add employee');
        $this->get('/employees/create')->assertForbidden()->assertSee('Employee changes are not authorized');
        $this->post('/employees', [
            'employee_code' => 'SYN-2',
            'display_name' => 'North Ben Sample',
        ])->assertForbidden();
        $this->assertSame(1, Employee::query()->where('company_id', $company->id)->count());
    }

    public function test_another_company_employee_and_episode_are_not_found(): void
    {
        $north = Company::query()->create([
            'company_name' => 'Synthetic North Workshop',
            'status' => 'Active',
        ]);
        $south = Company::query()->create([
            'company_name' => 'Synthetic South Workshop',
            'status' => 'Active',
        ]);
        $foreign = Employee::query()->create([
            'company_id' => $south->id,
            'employee_code' => 'SYN-9',
            'display_name' => 'South Cara Sample',
        ]);
        $episode = EmploymentEpisode::query()->create([
            'company_id' => $south->id,
            'employee_id' => $foreign->id,
            'joining_date' => '2026-01-01',
            'episode_status' => 'Open',
        ]);
        $this->signInFor($north);

        $this->get('/employees/'.$foreign->id)->assertNotFound();
        $this->get('/employees/'.$foreign->id.'/episodes/'.$episode->id.'/edit')->assertNotFound();
    }

    public function test_company_context_cannot_be_forged(): void
    {
        $north = Company::query()->create([
            'company_name' => 'Synthetic North Workshop',
            'status' => 'Active',
        ]);
        $south = Company::query()->create([
            'company_name' => 'Synthetic South Workshop',
            'status' => 'Active',
        ]);
        Employee::query()->create([
            'company_id' => $north->id,
            'employee_code' => 'SYN-1',
            'display_name' => 'North Ada Sample',
        ]);
        Employee::query()->create([
            'company_id' => $south->id,
            'employee_code' => 'SYN-1',
            'display_name' => 'South Ada Sample',
        ]);
        $this->signInFor($north, false);

        $this->get('/employees?company_id='.$south->id)
            ->assertOk()
            ->assertSee('North Ada Sample')
            ->assertDontSee('South Ada Sample');

        $this->post('/company', [
            'company_id' => $south->id,
            'can_manage_employees' => true,
        ])->assertSessionHasErrors('company_id');

        $this->assertSame($north->id, session(CompanyContext::SESSION_KEY));
        $this->assertFalse(
            CompanyMembership::query()->where('company_id', $north->id)->first()->can_manage_employees
        );
    }

    public function test_multiple_memberships_can_select_only_granted_companies(): void
    {
        $north = Company::query()->create([
            'company_name' => 'Synthetic North Workshop',
            'status' => 'Active',
        ]);
        $south = Company::query()->create([
            'company_name' => 'Synthetic South Workshop',
            'status' => 'Active',
        ]);
        $east = Company::query()->create([
            'company_name' => 'Synthetic East Workshop',
            'status' => 'Active',
        ]);
        Employee::query()->create([
            'company_id' => $north->id,
            'employee_code' => 'SYN-1',
            'display_name' => 'North Ada Sample',
        ]);
        Employee::query()->create([
            'company_id' => $south->id,
            'employee_code' => 'SYN-1',
            'display_name' => 'South Ada Sample',
        ]);

        $user = User::query()->create([
            'name' => 'Synthetic Clerk',
            'email' => 'multi@example.test',
            'password' => 'synthetic-passphrase-1',
        ]);
        foreach ([$north, $south] as $company) {
            CompanyMembership::query()->create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'status' => 'active',
                'can_manage_employees' => true,
            ]);
        }
        $this->actingAs($user);

        $this->get('/employees')->assertRedirect('/company');
        $this->get('/company')
            ->assertOk()
            ->assertSee('Synthetic North Workshop')
            ->assertSee('Synthetic South Workshop')
            ->assertDontSee('Synthetic East Workshop');

        $this->post('/company', ['company_id' => $east->id])->assertSessionHasErrors('company_id');
        $this->post('/company', ['company_id' => $south->id])->assertRedirect('/employees');
        $this->get('/employees')
            ->assertOk()
            ->assertSee('South Ada Sample')
            ->assertDontSee('North Ada Sample');
    }

    public function test_duplicate_membership_is_rejected(): void
    {
        $company = Company::query()->create([
            'company_name' => 'Synthetic North Workshop',
            'status' => 'Active',
        ]);
        $user = $this->signInFor($company, false);

        $this->expectException(QueryException::class);
        CompanyMembership::query()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'status' => 'inactive',
            'can_manage_employees' => true,
        ]);
    }

    public function test_there_is_no_http_path_to_create_a_membership(): void
    {
        $company = Company::query()->create([
            'company_name' => 'Synthetic North Workshop',
            'status' => 'Active',
        ]);
        $this->signInFor($company, true);

        $this->post('/company-memberships', [
            'company_id' => $company->id,
            'can_manage_employees' => true,
        ])->assertNotFound();
        $this->get('/register')->assertNotFound();
    }

    public function test_dev_provisioning_is_refused_outside_local_and_does_not_create_a_company(): void
    {
        $before = Company::query()->count();
        $usersBefore = User::query()->count();

        $this->artisan('hrms:provision-dev-user', [
            '--name' => 'Synthetic Clerk',
            '--email' => 'provision@example.test',
            '--password' => 'synthetic-passphrase-1',
            '--company' => '01ARZ3NDEKTSV4RRFFQ69G5FAV',
            '--manage-employees' => true,
        ])->assertFailed();

        $this->assertSame($before, Company::query()->count());
        $this->assertSame($usersBefore, User::query()->count());
    }

    public function test_dev_provisioning_grants_one_membership_when_local(): void
    {
        $companiesBefore = Company::query()->count();
        $company = Company::query()->create([
            'company_name' => 'Synthetic North Workshop',
            'status' => 'Active',
        ]);
        $this->app['env'] = 'local';

        $this->artisan('hrms:provision-dev-user', [
            '--name' => 'Synthetic Clerk',
            '--email' => 'Provision@example.test',
            '--password' => 'synthetic-passphrase-1',
            '--company' => $company->id,
        ])->assertSuccessful();

        $user = User::query()->where('email', 'provision@example.test')->first();
        $this->assertNotNull($user);
        $membership = CompanyMembership::query()->where('user_id', $user->id)->first();
        $this->assertSame($company->id, $membership->company_id);
        $this->assertFalse($membership->can_manage_employees);
        $this->assertSame($companiesBefore + 1, Company::query()->count());

        $this->artisan('hrms:provision-dev-user', [
            '--name' => 'Synthetic Clerk',
            '--email' => 'provision@example.test',
            '--password' => 'synthetic-passphrase-1',
            '--company' => $company->id,
            '--manage-employees' => true,
        ])->assertFailed();
        $this->assertFalse($membership->refresh()->can_manage_employees);
    }
}
