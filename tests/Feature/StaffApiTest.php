<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffApiTest extends TestCase
{
    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::first();
        if ($this->user) {
            $this->token = $this->user->createToken('test-device')->plainTextToken;
        }
    }

    /** @test */
    public function staff_api_login_requires_valid_credentials()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function staff_api_login_requires_email()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'password' => 'somepassword',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function staff_api_protected_routes_require_auth()
    {
        $routes = [
            'GET' => [
                '/api/v1/dashboard',
                '/api/v1/employee-profile',
                '/api/v1/employee-profile/staff-list',
                '/api/v1/attendance',
                '/api/v1/attendance/status',
                '/api/v1/leave-requests',
                '/api/v1/expenses',
                '/api/v1/billing/documents',
            ],
        ];

        foreach ($routes as $method => $endpoints) {
            foreach ($endpoints as $endpoint) {
                $response = $this->getJson($endpoint);
                $response->assertStatus(401);
            }
        }
    }

    /** @test */
    public function staff_api_auth_user_returns_profile()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/auth/user');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['id', 'name', 'email', 'roles'],
        ]);
    }

    /** @test */
    public function staff_api_dashboard_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/dashboard');

        $response->assertStatus(200);
    }

    /** @test */
    public function staff_api_employee_profile_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/employee-profile');

        $response->assertStatus(200);
    }

    /** @test */
    public function staff_api_staff_list_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/employee-profile/staff-list');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    /** @test */
    public function staff_api_attendance_status_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/attendance/status');

        $response->assertStatus(200);
    }

    /** @test */
    public function staff_api_leave_requests_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/leave-requests');

        $response->assertStatus(200);
    }

    /** @test */
    public function staff_api_leave_balance_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/leave-requests/balance');

        $response->assertStatus(200);
    }

    /** @test */
    public function staff_api_expenses_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/expenses');

        $response->assertStatus(200);
    }

    /** @test */
    public function staff_api_expense_categories_returns_categories()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/expenses/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name']
                ]
            ]);
    }

    /** @test */
    public function staff_api_billing_documents_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/billing/documents');

        $response->assertStatus(200);
    }

    /** @test */
    public function staff_api_billing_payments_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/billing/payments');

        $response->assertStatus(200);
    }

    /** @test */
    public function staff_api_billing_clients_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/billing/clients');

        $response->assertStatus(200);
    }

    /** @test */
    public function staff_api_material_requests_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/material-requests');

        $response->assertStatus(200);
    }

    /** @test */
    public function staff_api_logout_works()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
    }

    /** @test */
    public function staff_api_update_profile_works()
    {
        if (!$this->token || !$this->user) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/v1/auth/profile', [
            'name' => $this->user->name,
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function staff_api_leave_types_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/leave-requests/types');

        $response->assertStatus(200);
    }

    /** @test */
    public function staff_api_approvals_returns_pending_items()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/approvals');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['total', 'by_type']
            ]);
    }

    /** @test */
    public function staff_api_dashboard_calendar_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/dashboard/calendar');

        $response->assertStatus(200);
    }

    /** @test */
    public function staff_api_dashboard_activities_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/dashboard/activities');

        $response->assertStatus(200);
    }

    /** @test */
    public function staff_api_dashboard_invoices_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/dashboard/invoices');

        $response->assertStatus(200);
    }

    /** @test */
    public function staff_api_dashboard_followups_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/dashboard/followups');

        $response->assertStatus(200);
    }

    /** @test */
    public function staff_api_dashboard_recent_activities_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/dashboard/recent-activities');

        $response->assertStatus(200);
    }

    /** @test */
    public function staff_api_dashboard_project_status_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/dashboard/project-status');

        $response->assertStatus(200);
    }

    /** @test */
    public function staff_api_staff_bank_details_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/staff-bank-details');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data', 'meta']);
    }

    /** @test */
    public function staff_api_adjustments_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/adjustments');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data', 'meta']);
    }

    /** @test */
    public function staff_api_accounting_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/accounting');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'outstanding_invoices',
                    'overdue_invoices',
                    'recent_invoices',
                    'recent_payments'
                ]
            ]);
    }

    /** @test */
    public function staff_api_procurement_dashboard_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/procurement/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'material_requests',
                    'quotations',
                    'purchases',
                    'inspections'
                ]
            ]);
    }

    /** @test */
    public function staff_api_supplier_quotations_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/procurement/supplier-quotations');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data', 'meta']);
    }

    /** @test */
    public function staff_api_purchases_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/procurement/purchases');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data', 'meta']);
    }

    /** @test */
    public function staff_api_inspections_loads()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated user found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/procurement/inspections');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data', 'meta']);
    }
}
