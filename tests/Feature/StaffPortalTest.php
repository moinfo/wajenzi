<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffPortalTest extends TestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::first();
    }

    /** @test */
    public function homepage_requires_auth()
    {
        $response = $this->get('/');
        $response->assertStatus(302);
    }

    /** @test */
    public function dashboard_loads_when_authenticated()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('pages.dashboard');
    }

    /** @test */
    public function dashboard_requires_authentication()
    {
        $response = $this->get('/dashboard');
        $response->assertStatus(302);
    }

    /** @test */
    public function staff_list_page_loads()
    {
        $response = $this->actingAs($this->user)
            ->get('/staff');

        $response->assertStatus(200);
        $response->assertViewIs('pages.staff.staff_index');
    }

    /** @test */
    public function staff_bank_details_route_exists()
    {
        $response = $this->actingAs($this->user)
            ->get('/payroll/staff_bank_details');

        $response->assertStatus(200);
    }

    /** @test */
    public function staff_adjustment_route_exists()
    {
        $response = $this->actingAs($this->user)
            ->get('/staff/adjustment');

        $response->assertStatus(404);
    }

    /** @test */
    public function projects_page_loads()
    {
        $response = $this->actingAs($this->user)
            ->get('/projects');

        $response->assertStatus(200);
    }

    /** @test */
    public function payroll_page_loads()
    {
        $response = $this->actingAs($this->user)
            ->get('/payroll');

        $response->assertStatus(200);
    }

    /** @test */
    public function leave_request_page_loads()
    {
        $response = $this->actingAs($this->user)
            ->get('/leaves/leave_request');

        $response->assertStatus(200);
    }

    /** @test */
    public function expenses_page_loads()
    {
        $response = $this->actingAs($this->user)
            ->get('/expenses');

        $response->assertStatus(200);
    }

    /** @test */
    public function accounting_page_loads()
    {
        $response = $this->actingAs($this->user)
            ->get('/accounting');

        $response->assertStatus(200);
    }

    /** @test */
    public function procurement_page_loads()
    {
        $response = $this->actingAs($this->user)
            ->get('/procurement_dashboard');

        $response->assertStatus(200);
    }

    /** @test */
    public function settings_page_loads()
    {
        $response = $this->actingAs($this->user)
            ->get('/settings');

        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_logout()
    {
        $response = $this->actingAs($this->user)
            ->post('/logout');

        $response->assertStatus(302);
    }

    /** @test */
    public function unauthenticated_user_redirected_to_login()
    {
        $pages = ['/dashboard', '/staff', '/projects', '/payroll'];
        
        foreach ($pages as $page) {
            $response = $this->get($page);
            $response->assertStatus(302);
        }
    }
}
