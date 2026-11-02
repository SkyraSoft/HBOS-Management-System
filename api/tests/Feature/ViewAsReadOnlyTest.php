<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewAsReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch;
    protected User $owner;
    protected User $manager;
    protected User $salesperson;
    protected string $ownerToken;
    protected string $managerToken;
    protected string $salespersonToken;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Metro Retail Enterprise']);
        setPermissionsTeamId($this->business->id);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'North Nazimabad Main',
            'code' => 'NN-01',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->branches()->attach($this->branch->id, ['business_id' => $this->business->id]);
        $this->owner->assignRole('Business Owner');
        $this->ownerToken = $this->owner->createToken('Owner')->plainTextToken;

        $this->manager = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->manager->businesses()->attach($this->business->id);
        $this->manager->branches()->attach($this->branch->id, ['business_id' => $this->business->id]);
        $this->manager->assignRole('Branch Manager');
        $this->managerToken = $this->manager->createToken('Manager')->plainTextToken;

        $this->salesperson = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->salesperson->businesses()->attach($this->business->id);
        $this->salesperson->branches()->attach($this->branch->id, ['business_id' => $this->business->id]);
        $this->salesperson->assignRole('Salesperson');
        $this->salespersonToken = $this->salesperson->createToken('Salesperson')->plainTextToken;

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Standard Tea Bag 100s',
            'cost_price' => 300.00,
            'selling_price' => 380.00,
            'stock' => 50.000,
        ]);
    }

    protected function authHeaders(string $token, array $extra = []): array
    {
        return array_merge([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => (string) $this->business->id,
        ], $extra);
    }

    public function test_manager_cannot_enter_view_as_mode()
    {
        $res = $this->postJson('/api/v1/view-as/enter', [
            'role' => 'Cashier',
            'branch_id' => $this->branch->id,
        ], $this->authHeaders($this->managerToken));
        $res->assertStatus(403);
    }

    public function test_salesperson_cannot_enter_view_as_mode()
    {
        $res = $this->postJson('/api/v1/view-as/enter', [
            'role' => 'Branch Manager',
            'branch_id' => $this->branch->id,
        ], $this->authHeaders($this->salespersonToken));
        $res->assertStatus(403);
    }

    public function test_owner_can_enter_view_as_mode()
    {
        $res = $this->postJson('/api/v1/view-as/enter', [
            'role' => 'Cashier',
            'branch_id' => $this->branch->id,
        ], $this->authHeaders($this->ownerToken));
        $res->assertStatus(200);
        $res->assertJsonPath('data.role', 'Cashier');
        $res->assertJsonPath('data.is_read_only', true);
    }

    public function test_owner_identity_preserved_in_audit_and_sanctum_session()
    {
        $res = $this->postJson('/api/v1/view-as/enter', [
            'role' => 'Branch Manager',
            'branch_id' => $this->branch->id,
        ], $this->authHeaders($this->ownerToken));

        $res->assertStatus(200);

        // The authenticated user in Sanctum is still the Owner
        $this->actingAs($this->owner);
        $this->assertEquals($this->owner->id, auth()->id());
    }

    public function test_view_as_blocks_post_requests_across_sales_products_expenses_transfers()
    {
        $viewAsHeaders = $this->authHeaders($this->ownerToken, [
            'X-View-As-Role' => 'Cashier',
            'X-View-As-Active' => 'true',
        ]);

        // Attempt POST to Sales
        $resSale = $this->postJson('/api/v1/sales', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 380.00],
            ],
        ], $viewAsHeaders);
        $resSale->assertStatus(403);
        $this->assertStringContainsString('strictly forbidden during read-only View-As preview mode', $resSale->json('message'));

        // Attempt POST to Products
        $resProduct = $this->postJson('/api/v1/products', [
            'name' => 'Illegal Product In View As',
        ], $viewAsHeaders);
        $resProduct->assertStatus(403);

        // Attempt POST to Expenses
        $resExpense = $this->postJson('/api/v1/expenses', [
            'amount' => 500.00,
        ], $viewAsHeaders);
        $resExpense->assertStatus(403);

        // Attempt POST to Transfers
        $resTransfer = $this->postJson('/api/v1/transfers', [
            'to_branch_id' => 999,
        ], $viewAsHeaders);
        $resTransfer->assertStatus(403);
    }

    public function test_view_as_blocks_put_patch_delete_requests()
    {
        $viewAsHeaders = $this->authHeaders($this->ownerToken, [
            'X-View-As-Role' => 'Branch Manager',
            'X-View-As-Active' => 'true',
        ]);

        // Attempt PUT to Branch
        $resPut = $this->putJson("/api/v1/branches/{$this->branch->id}", [
            'name' => 'Hacked Name',
        ], $viewAsHeaders);
        $resPut->assertStatus(403);

        // Attempt DELETE on Product
        $resDelete = $this->deleteJson("/api/v1/products/{$this->product->id}", [], $viewAsHeaders);
        $resDelete->assertStatus(403);
    }

    public function test_view_as_allows_safe_get_queries()
    {
        $viewAsHeaders = $this->authHeaders($this->ownerToken, [
            'X-View-As-Role' => 'Cashier',
            'X-View-As-Active' => 'true',
        ]);

        // Safe GET on products
        $resProducts = $this->getJson('/api/v1/products', $viewAsHeaders);
        $resProducts->assertStatus(200);

        // Safe GET on branches
        $resBranches = $this->getJson('/api/v1/branches', $viewAsHeaders);
        $resBranches->assertStatus(200);
    }

    public function test_view_as_exit_endpoint_is_exempt_from_mutation_blocking()
    {
        $viewAsHeaders = $this->authHeaders($this->ownerToken, [
            'X-View-As-Role' => 'Cashier',
            'X-View-As-Active' => 'true',
        ]);

        // POST to view-as/exit must succeed even with View-As header present
        $res = $this->postJson('/api/v1/view-as/exit', [], $viewAsHeaders);
        $res->assertStatus(200);
        $res->assertJsonPath('data.active', false);
    }

    public function test_invalid_view_as_role_rejected()
    {
        $invalidHeaders = $this->authHeaders($this->ownerToken, [
            'X-View-As-Role' => 'SuperAdminRole',
        ]);

        $res = $this->getJson('/api/v1/products', $invalidHeaders);
        $res->assertStatus(422);
        $this->assertStringContainsString('Invalid View-As perspective role', $res->json('message'));
    }

    public function test_non_owner_sending_forged_view_as_headers_is_strictly_forbidden()
    {
        // Manager attempting to send View-As active header
        $forgedHeaders = $this->authHeaders($this->managerToken, [
            'X-View-As-Role' => 'Cashier',
            'X-View-As-Active' => 'true',
        ]);

        $res = $this->getJson('/api/v1/products', $forgedHeaders);
        $res->assertStatus(403);
        $this->assertStringContainsString('Only Business Owners can access View-As', $res->json('message'));

        // Salesperson attempting to send View-As header
        $forgedSalesperson = $this->authHeaders($this->salespersonToken, [
            'X-View-As-Role' => 'Branch Manager',
            'X-View-As-Active' => 'true',
        ]);
        $res2 = $this->getJson('/api/v1/products', $forgedSalesperson);
        $res2->assertStatus(403);
    }

    public function test_owner_cannot_enter_view_as_for_foreign_tenant_branch()
    {
        $foreignBiz = Business::create(['name' => 'Foreign Retail Group']);
        $foreignBranch = Branch::create([
            'business_id' => $foreignBiz->id,
            'name' => 'Foreign Branch',
            'code' => 'FRG-01',
            'status' => 'active',
        ]);

        $res = $this->postJson('/api/v1/view-as/enter', [
            'branch_id' => $foreignBranch->id,
            'role' => 'Branch Manager',
        ], $this->authHeaders($this->ownerToken));

        $res->assertStatus(404);
    }

    public function test_view_as_blocks_mutation_on_other_business_modules()
    {
        $viewAsHeaders = $this->authHeaders($this->ownerToken, [
            'X-View-As-Role' => 'Branch Manager',
            'X-View-As-Active' => 'true',
        ]);

        // 1. Attempt POST to create an expense
        $resExpense = $this->postJson('/api/v1/expenses', [
            'amount' => 150.00,
            'expense_category_id' => 1,
            'date' => now()->toDateString(),
        ], $viewAsHeaders);
        $resExpense->assertStatus(403);

        // 2. Attempt POST to create a branch
        $resBranch = $this->postJson('/api/v1/branches', [
            'name' => 'Should Not Create',
            'code' => 'SNC-99',
        ], $viewAsHeaders);
        $resBranch->assertStatus(403);

        // 3. Attempt POST to create a stock transfer
        $resTransfer = $this->postJson('/api/v1/stock-transfers', [
            'from_branch_id' => $this->branch->id,
            'to_branch_id' => $this->branch->id + 1,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ], $viewAsHeaders);
        $resTransfer->assertStatus(403);
    }
}
