<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchActionNote;
use App\Models\Business;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchActionNotesTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch1;
    protected Branch $branch2;
    protected User $owner;
    protected User $manager1;
    protected User $manager2;
    protected User $salesperson;
    protected string $ownerToken;
    protected string $manager1Token;
    protected string $manager2Token;
    protected string $salespersonToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Metro Retail Enterprise']);
        setPermissionsTeamId($this->business->id);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->branch1 = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'North Nazimabad Main',
            'code' => 'NN-01',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->branch2 = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Gulshan Branch',
            'code' => 'GUL-02',
            'is_primary' => false,
            'status' => 'active',
        ]);

        // Owner
        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->branches()->attach([$this->branch1->id, $this->branch2->id], ['business_id' => $this->business->id]);
        $this->owner->assignRole('Business Owner');
        $this->ownerToken = $this->owner->createToken('Owner')->plainTextToken;

        // Manager at Branch 1
        $this->manager1 = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
        ]);
        $this->manager1->businesses()->attach($this->business->id);
        $this->manager1->branches()->attach($this->branch1->id, ['business_id' => $this->business->id]);
        $this->manager1->assignRole('Branch Manager');
        $this->manager1Token = $this->manager1->createToken('Manager1')->plainTextToken;

        // Manager at Branch 2
        $this->manager2 = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch2->id,
        ]);
        $this->manager2->businesses()->attach($this->business->id);
        $this->manager2->branches()->attach($this->branch2->id, ['business_id' => $this->business->id]);
        $this->manager2->assignRole('Branch Manager');
        $this->manager2Token = $this->manager2->createToken('Manager2')->plainTextToken;

        // Salesperson at Branch 1
        $this->salesperson = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
        ]);
        $this->salesperson->businesses()->attach($this->business->id);
        $this->salesperson->branches()->attach($this->branch1->id, ['business_id' => $this->business->id]);
        $this->salesperson->assignRole('Salesperson');
        $this->salespersonToken = $this->salesperson->createToken('Salesperson')->plainTextToken;
    }

    protected function authHeaders(string $token, array $extra = []): array
    {
        return array_merge([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => (string) $this->business->id,
        ], $extra);
    }

    public function test_owner_can_create_action_note_for_branch()
    {
        $res = $this->postJson('/api/v1/action-notes', [
            'branch_id' => $this->branch1->id,
            'title' => 'Audit expiry dates on confectionery shelf',
            'description' => 'Conduct physical stock verification by Friday 5 PM.',
            'priority' => 'high',
        ], $this->authHeaders($this->ownerToken));

        $res->assertStatus(201);
        $res->assertJsonPath('data.title', 'Audit expiry dates on confectionery shelf');
        $res->assertJsonPath('data.status', 'open');
        $res->assertJsonPath('data.priority', 'high');
        $res->assertJsonPath('data.branch_id', $this->branch1->id);
    }

    public function test_branch_manager_can_list_action_notes_for_assigned_branch()
    {
        // Create note for Branch 1
        BranchActionNote::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'created_by' => $this->owner->id,
            'title' => 'Inspect oil stock level',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $res = $this->getJson('/api/v1/action-notes', $this->authHeaders($this->manager1Token));
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('Inspect oil stock level', $res->json('data.0.title'));
    }

    public function test_cross_branch_manager_cannot_view_or_access_foreign_branch_notes()
    {
        // Create note for Branch 2
        BranchActionNote::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch2->id,
            'created_by' => $this->owner->id,
            'title' => 'Secret expansion note for Branch 2',
            'priority' => 'urgent',
            'status' => 'open',
        ]);

        // Manager of Branch 1 queries notes for Branch 2 explicitly
        $res = $this->getJson('/api/v1/action-notes?branch_id=' . $this->branch2->id, $this->authHeaders($this->manager1Token));
        $res->assertStatus(403);
        $this->assertStringContainsString('do not have access to notes for this branch', $res->json('message'));
    }

    public function test_salesperson_forbidden_from_action_notes()
    {
        $res = $this->getJson('/api/v1/action-notes', $this->authHeaders($this->salespersonToken));
        $res->assertStatus(403);
        $this->assertStringContainsString('Cashiers and unauthorized roles cannot access branch action notes', $res->json('message'));

        $resPost = $this->postJson('/api/v1/action-notes', [
            'branch_id' => $this->branch1->id,
            'title' => 'Unauthorized note',
        ], $this->authHeaders($this->salespersonToken));
        $resPost->assertStatus(422);
    }

    public function test_action_note_status_lifecycle_and_mandatory_resolution_notes()
    {
        $note = BranchActionNote::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'created_by' => $this->owner->id,
            'title' => 'Check refrigerator temperature log',
            'priority' => 'high',
            'status' => 'open',
        ]);

        // 1. Transition: open -> in_progress
        $resInProgress = $this->patchJson("/api/v1/action-notes/{$note->id}/status", [
            'status' => 'in_progress',
        ], $this->authHeaders($this->manager1Token));

        $resInProgress->assertStatus(200);
        $resInProgress->assertJsonPath('data.status', 'in_progress');

        // 2. Transition without resolution_notes must fail with 422
        $resMissingNotes = $this->patchJson("/api/v1/action-notes/{$note->id}/status", [
            'status' => 'resolved',
        ], $this->authHeaders($this->manager1Token));

        $resMissingNotes->assertStatus(422);
        $this->assertStringContainsString('Mandatory resolution notes are required', $resMissingNotes->json('message'));

        // 3. Transition: in_progress -> resolved with resolution_notes succeeds
        $resResolved = $this->patchJson("/api/v1/action-notes/{$note->id}/status", [
            'status' => 'resolved',
            'resolution_notes' => 'Temperature verified at 3.5C. Compressor serviced.',
        ], $this->authHeaders($this->manager1Token));

        $resResolved->assertStatus(200);
        $resResolved->assertJsonPath('data.status', 'resolved');
        $resResolved->assertJsonPath('data.resolution_notes', 'Temperature verified at 3.5C. Compressor serviced.');
        $this->assertNotNull($resResolved->json('data.resolved_at'));
    }

    public function test_pending_count_for_owner_aggregates_across_business()
    {
        // 2 notes for Branch 1 (1 open, 1 in_progress)
        BranchActionNote::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'created_by' => $this->owner->id,
            'title' => 'Note 1',
            'status' => 'open',
        ]);

        BranchActionNote::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'created_by' => $this->owner->id,
            'title' => 'Note 2',
            'status' => 'in_progress',
        ]);

        // 1 resolved note for Branch 1 (should NOT count as pending)
        BranchActionNote::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'created_by' => $this->owner->id,
            'title' => 'Note 3 Resolved',
            'status' => 'resolved',
        ]);

        // 1 open note for Branch 2
        BranchActionNote::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch2->id,
            'created_by' => $this->owner->id,
            'title' => 'Note 4 Branch 2',
            'status' => 'open',
        ]);

        // Owner should see total pending across business (3 notes: 2 at Branch 1 + 1 at Branch 2)
        $resOwner = $this->getJson('/api/v1/action-notes/pending-count', $this->authHeaders($this->ownerToken));
        $resOwner->assertStatus(200);
        $this->assertEquals(3, $resOwner->json('data.pending_count'));
    }

    public function test_pending_count_for_manager_scoped_to_assigned_branch()
    {
        // 2 notes for Branch 1 (1 open, 1 in_progress)
        BranchActionNote::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'created_by' => $this->owner->id,
            'title' => 'Note 1',
            'status' => 'open',
        ]);

        BranchActionNote::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'created_by' => $this->owner->id,
            'title' => 'Note 2',
            'status' => 'in_progress',
        ]);

        // 1 open note for Branch 2 (manager 1 is not assigned here)
        BranchActionNote::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch2->id,
            'created_by' => $this->owner->id,
            'title' => 'Note 4 Branch 2',
            'status' => 'open',
        ]);

        // Manager 1 should only see pending for Branch 1 (2 notes)
        $resManager1 = $this->getJson('/api/v1/action-notes/pending-count', $this->authHeaders($this->manager1Token));
        $resManager1->assertStatus(200);
        $this->assertEquals(2, $resManager1->json('data.pending_count'));
    }
}
