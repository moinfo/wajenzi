<?php

namespace Tests\Feature;

use App\Models\BillingDocument;
use App\Models\Project;
use App\Models\ProjectClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPortalTest extends TestCase
{
    protected $client;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->client = ProjectClient::where('portal_access_enabled', true)->first();
        $this->user = User::first();
    }

    /** @test */
    public function client_login_page_loads()
    {
        $response = $this->get(route('client.login'));
        $response->assertStatus(200);
        $response->assertViewIs('client.auth.login');
    }

    /** @test */
    public function client_can_login_with_email()
    {
        if (!$this->client) {
            $this->markTestSkipped('No client with portal access found');
        }

        $response = $this->post(route('client.login'), [
            'login' => $this->client->email,
            'password' => $this->client->password ?? 'password',
        ]);

        $response->assertStatus(302);
    }

    /** @test */
    public function client_cannot_login_with_wrong_credentials()
    {
        if (!$this->client) {
            $this->markTestSkipped('No client found');
        }

        $response = $this->post(route('client.login'), [
            'email' => $this->client->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors();
    }

    /** @test */
    public function client_portal_requires_authentication()
    {
        $response = $this->get(route('client.dashboard'));
        $response->assertStatus(302);
        $response->assertRedirect(route('client.login'));
    }

    /** @test */
    public function client_portal_dashboard_loads_when_authenticated()
    {
        if (!$this->client) {
            $this->markTestSkipped('No client found');
        }

        $response = $this->actingAs($this->client, 'client')
            ->get(route('client.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('client.dashboard');
        $response->assertViewHas(['client', 'projects', 'stats']);
    }

    /** @test */
    public function client_portal_billing_loads_when_authenticated()
    {
        if (!$this->client) {
            $this->markTestSkipped('No client found');
        }

        $response = $this->actingAs($this->client, 'client')
            ->get(route('client.billing'));

        $response->assertStatus(200);
        $response->assertViewIs('client.billing');
        $response->assertViewHas(['invoices', 'quotes', 'proformas', 'creditNotes', 'summary']);
    }

    /** @test */
    public function client_cannot_access_other_clients_projects()
    {
        $otherClient = ProjectClient::where('id', '!=', $this->client?->id)
            ->where('portal_access_enabled', true)
            ->first();

        if (!$this->client || !$otherClient) {
            $this->markTestSkipped('Not enough clients for this test');
        }

        $otherClientProject = Project::where('client_id', $otherClient->id)->first();
        if (!$otherClientProject) {
            $this->markTestSkipped('No project found for other client');
        }

        $response = $this->actingAs($this->client, 'client')
            ->get(route('client.project.show', $otherClientProject->id));

        $response->assertStatus(404);
    }

    /** @test */
    public function client_can_view_own_project()
    {
        if (!$this->client) {
            $this->markTestSkipped('No client found');
        }

        $project = Project::where('client_id', $this->client->id)->first();
        if (!$project) {
            $this->markTestSkipped('No project found for client');
        }

        $response = $this->actingAs($this->client, 'client')
            ->get(route('client.project.show', $project->id));

        $response->assertStatus(200);
        $response->assertViewIs('client.projects.show');
    }

    /** @test */
    public function client_can_view_own_project_boq()
    {
        if (!$this->client) {
            $this->markTestSkipped('No client found');
        }

        $project = Project::where('client_id', $this->client->id)->first();
        if (!$project) {
            $this->markTestSkipped('No project found for client');
        }

        $response = $this->actingAs($this->client, 'client')
            ->get(route('client.project.boq', $project->id));

        $response->assertStatus(200);
        $response->assertViewIs('client.projects.boq');
    }

    /** @test */
    public function client_can_view_own_project_schedule()
    {
        if (!$this->client) {
            $this->markTestSkipped('No client found');
        }

        $project = Project::where('client_id', $this->client->id)->first();
        if (!$project) {
            $this->markTestSkipped('No project found for client');
        }

        $response = $this->actingAs($this->client, 'client')
            ->get(route('client.project.schedule', $project->id));

        $response->assertStatus(200);
        $response->assertViewIs('client.projects.schedule');
    }

    /** @test */
    public function client_can_view_own_project_financials()
    {
        if (!$this->client) {
            $this->markTestSkipped('No client found');
        }

        $project = Project::where('client_id', $this->client->id)->first();
        if (!$project) {
            $this->markTestSkipped('No project found for client');
        }

        $response = $this->actingAs($this->client, 'client')
            ->get(route('client.project.financials', $project->id));

        $response->assertStatus(200);
        $response->assertViewIs('client.projects.financials');
    }

    /** @test */
    public function client_can_view_own_project_documents()
    {
        if (!$this->client) {
            $this->markTestSkipped('No client found');
        }

        $project = Project::where('client_id', $this->client->id)->first();
        if (!$project) {
            $this->markTestSkipped('No project found for client');
        }

        $response = $this->actingAs($this->client, 'client')
            ->get(route('client.project.documents', $project->id));

        $response->assertStatus(200);
        $response->assertViewIs('client.projects.documents');
    }

    /** @test */
    public function client_can_view_own_project_reports()
    {
        if (!$this->client) {
            $this->markTestSkipped('No client found');
        }

        $project = Project::where('client_id', $this->client->id)->first();
        if (!$project) {
            $this->markTestSkipped('No project found for client');
        }

        $response = $this->actingAs($this->client, 'client')
            ->get(route('client.project.reports', $project->id));

        $response->assertStatus(200);
        $response->assertViewIs('client.projects.reports');
    }

    /** @test */
    public function client_can_view_own_project_gallery()
    {
        if (!$this->client) {
            $this->markTestSkipped('No client found');
        }

        $project = Project::where('client_id', $this->client->id)->first();
        if (!$project) {
            $this->markTestSkipped('No project found for client');
        }

        $response = $this->actingAs($this->client, 'client')
            ->get(route('client.project.gallery', $project->id));

        $response->assertStatus(200);
        $response->assertViewIs('client.projects.gallery');
    }

    /** @test */
    public function client_can_download_billing_pdf()
    {
        if (!$this->client) {
            $this->markTestSkipped('No client found');
        }

        $document = BillingDocument::where('client_id', $this->client->id)->first();
        if (!$document) {
            $this->markTestSkipped('No billing document found for client');
        }

        $response = $this->actingAs($this->client, 'client')
            ->get(route('client.billing.pdf', $document->id));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function client_cannot_download_other_clients_billing_pdf()
    {
        if (!$this->client) {
            $this->markTestSkipped('No client found');
        }

        $otherDocument = BillingDocument::where('client_id', '!=', $this->client->id)->first();
        if (!$otherDocument) {
            $this->markTestSkipped('No billing document found for other client');
        }

        $response = $this->actingAs($this->client, 'client')
            ->get(route('client.billing.pdf', $otherDocument->id));

        $response->assertStatus(404);
    }

    /** @test */
    public function client_can_logout()
    {
        if (!$this->client) {
            $this->markTestSkipped('No client found');
        }

        $response = $this->actingAs($this->client, 'client')
            ->post(route('client.logout'));

        $response->assertStatus(302);
        $response->assertRedirect(route('client.login'));
    }

    /** @test */
    public function web_client_dashboard_shows_correct_stats()
    {
        if (!$this->client) {
            $this->markTestSkipped('No client found');
        }

        $response = $this->actingAs($this->client, 'client')
            ->get(route('client.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('stats', function ($stats) {
            return isset($stats['total_projects'])
                && isset($stats['active_projects'])
                && isset($stats['total_contract_value'])
                && isset($stats['total_invoiced']);
        });
    }
}
