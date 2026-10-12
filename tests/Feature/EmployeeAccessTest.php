<?php

namespace Tests\Feature;

use Tests\TestCase;

class EmployeeAccessTest extends TestCase
{
    public function test_guests_are_sent_to_login_from_employee_routes(): void
    {
        $this->get('/employees')->assertRedirect('/login');
        $this->get('/employees/01ARZ3NDEKTSV4RRFFQ69G5FAV')->assertRedirect('/login');
        $this->getJson('/employees')->assertUnauthorized();
    }

    public function test_guest_posts_are_sent_to_login(): void
    {
        $this->post('/employees', [
            'employee_code' => 'SYN-1',
            'display_name' => 'Synthetic Person',
            'company_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV',
        ])->assertRedirect('/login');

        $this->post('/employees/01ARZ3NDEKTSV4RRFFQ69G5FAV/episodes', [
            'joining_date' => '2026-01-01',
            'episode_status' => 'Open',
        ])->assertRedirect('/login');
    }
}
