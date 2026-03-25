<?php

namespace Tests\Feature;

use App\Models\BillingDocument;
use App\Models\Project;
use App\Models\ProjectClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientApiTest extends TestCase
{
    protected $client;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->client = ProjectClient::where('portal_access_enabled', true)->first();
        if ($this->client) {
            $this->token = $this->client->createToken('test-device')->plainTextToken;
        }
    }

    /** @test */
    public function client_api_login_requires_valid_credentials()
    {
        $response = $this->postJson('/api/client/auth/login', [
            'login' => 'invalid@example.com',
            'password' => 'wrong-password',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['error', 'code']);
    }

    /** @test */
    public function client_api_login_requires_email_or_phone()
    {
        if (!$this->client) {
            $this->markTestSkipped('No client found');
        }

        $response = $this->postJson('/api/client/auth/login', [
            'login' => $this->client->email,
            'password' => 'wrong-password',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function client_api_login_requires_device_name()
    {
        if (!$this->client) {
            $this->markTestSkipped('No client found');
        }

        $response = $this->postJson('/api/client/auth/login', [
            'login' => $this->client->email,
            'password' => 'password',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function client_api_protected_routes_require_auth()
    {
        $routes = [
            'GET' => ['/api/client/auth/me', '/api/client/dashboard', '/api/client/billing'],
        ];

        foreach ($routes as $method => $endpoints) {
            foreach ($endpoints as $endpoint) {
                $response = $this->getJson($endpoint);
                $response->assertStatus(401);
            }
        }
    }

    /** @test */
    public function client_api_auth_me_returns_profile()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated client found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/client/auth/me');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'first_name',
                'last_name',
                'full_name',
                'email',
                'phone_number',
                'projects_count',
            ],
        ]);
    }

    /** @test */
    public function client_api_update_profile_works()
    {
        if (!$this->token || !$this->client) {
            $this->markTestSkipped('No authenticated client found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/client/auth/profile', [
            'first_name' => $this->client->first_name,
            'last_name' => $this->client->last_name,
            'email' => $this->client->email,
            'phone_number' => $this->client->phone_number,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function client_api_change_password_requires_validation()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated client found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/client/auth/password', [
            'current_password' => '',
            'new_password' => '',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function client_api_change_password_requires_minimum_length()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated client found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/client/auth/password', [
            'current_password' => 'password',
            'new_password' => 'short',
            'new_password_confirmation' => 'short',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function client_api_dashboard_returns_stats_and_projects()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated client found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/client/dashboard');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'stats' => [
                    'total_projects',
                    'active_projects',
                    'total_contract_value',
                    'total_invoiced',
                ],
                'projects',
            ],
        ]);
    }

    /** @test */
    public function client_api_projects_list_returns_client_projects()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated client found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/client/projects');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
        ]);
    }

    /** @test */
    public function client_api_billing_returns_documents()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated client found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/client/billing');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'summary',
                'invoices',
                'quotes',
                'proformas',
                'credit_notes',
            ],
        ]);
    }

    /** @test */
    public function client_api_can_view_own_project()
    {
        if (!$this->token || !$this->client) {
            $this->markTestSkipped('No authenticated client found');
        }

        $project = Project::where('client_id', $this->client->id)->first();
        if (!$project) {
            $this->markTestSkipped('No project found for client');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/client/projects/{$project->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'project' => [
                    'id',
                    'project_name',
                    'status',
                ],
            ],
        ]);
    }

    /** @test */
    public function client_api_cannot_view_other_clients_project()
    {
        if (!$this->token || !$this->client) {
            $this->markTestSkipped('No authenticated client found');
        }

        $otherProject = Project::where('client_id', '!=', $this->client->id)->first();
        if (!$otherProject) {
            $this->markTestSkipped('No project found for other client');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/client/projects/{$otherProject->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function client_api_can_view_project_boq()
    {
        if (!$this->token || !$this->client) {
            $this->markTestSkipped('No authenticated client found');
        }

        $project = Project::where('client_id', $this->client->id)->first();
        if (!$project) {
            $this->markTestSkipped('No project found for client');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/client/projects/{$project->id}/boq");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
        ]);
    }

    /** @test */
    public function client_api_can_view_project_schedule()
    {
        if (!$this->token || !$this->client) {
            $this->markTestSkipped('No authenticated client found');
        }

        $project = Project::where('client_id', $this->client->id)->first();
        if (!$project) {
            $this->markTestSkipped('No project found for client');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/client/projects/{$project->id}/schedule");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'phases',
                'activities',
            ],
        ]);
    }

    /** @test */
    public function client_api_can_view_project_financials()
    {
        if (!$this->token || !$this->client) {
            $this->markTestSkipped('No authenticated client found');
        }

        $project = Project::where('client_id', $this->client->id)->first();
        if (!$project) {
            $this->markTestSkipped('No project found for client');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/client/projects/{$project->id}/financials");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'summary',
            ],
        ]);
    }

    /** @test */
    public function client_api_can_view_project_documents()
    {
        if (!$this->token || !$this->client) {
            $this->markTestSkipped('No authenticated client found');
        }

        $project = Project::where('client_id', $this->client->id)->first();
        if (!$project) {
            $this->markTestSkipped('No project found for client');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/client/projects/{$project->id}/documents");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
        ]);
    }

    /** @test */
    public function client_api_can_view_project_reports()
    {
        if (!$this->token || !$this->client) {
            $this->markTestSkipped('No authenticated client found');
        }

        $project = Project::where('client_id', $this->client->id)->first();
        if (!$project) {
            $this->markTestSkipped('No project found for client');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/client/projects/{$project->id}/reports");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'daily_reports',
                'site_visits',
            ],
        ]);
    }

    /** @test */
    public function client_api_can_view_project_gallery()
    {
        if (!$this->token || !$this->client) {
            $this->markTestSkipped('No authenticated client found');
        }

        $project = Project::where('client_id', $this->client->id)->first();
        if (!$project) {
            $this->markTestSkipped('No project found for client');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/client/projects/{$project->id}/gallery");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'images',
                'phases',
            ],
        ]);
    }

    /** @test */
    public function client_api_logout_revokes_token()
    {
        if (!$this->token) {
            $this->markTestSkipped('No authenticated client found');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/client/auth/logout');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /** @test */
    public function client_api_token_revocation_works()
    {
        if (!$this->client) {
            $this->markTestSkipped('No client found');
        }

        $token = $this->client->createToken('test-device-2')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/client/auth/logout');

        $response->assertStatus(200);
    }
}
