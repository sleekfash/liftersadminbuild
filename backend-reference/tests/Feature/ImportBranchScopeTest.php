<?php
namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Branch;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\ImportSheetSnapshot;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Integration coverage for the `import_rows_xbranch` invariant: import row
 * listings and every alternate import route are strictly scoped to the branch
 * that owns the batch, not the uploader's current posting.
 */
class ImportBranchScopeTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchA;
    private Branch $branchB;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (RoleCode::cases() as $role) {
            Role::create(['code' => $role->value, 'name' => $role->value]);
        }

        $this->branchA = Branch::create(['name' => 'Branch A', 'code' => 'BRA', 'is_active' => true]);
        $this->branchB = Branch::create(['name' => 'Branch B', 'code' => 'BRB', 'is_active' => true]);
    }

    private function user(?Branch $branch, string $roleCode): User
    {
        $user = User::create([
            'branch_id' => $branch?->id,
            'name' => 'User ' . uniqid(),
            'email' => uniqid() . '@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->roles()->attach(Role::where('code', $roleCode)->value('id'));

        return $user->fresh();
    }

    private function batchFor(Branch $branch, User $uploader): ImportBatch
    {
        $batch = ImportBatch::create([
            'uploaded_by' => $uploader->id,
            'branch_id' => $branch->id,
            'filename' => "{$branch->code}.xlsx",
            'status' => 'UPLOADED',
            'metadata' => [],
        ]);

        $sheet = ImportSheetSnapshot::create([
            'import_batch_id' => $batch->id,
            'sheet_name' => 'TB',
            'title_blocks' => [],
            'heading_map' => [],
            'raw_payload' => [],
        ]);

        ImportRow::create([
            'import_batch_id' => $batch->id,
            'import_sheet_snapshot_id' => $sheet->id,
            'row_number' => 1,
            'status' => 'PENDING',
            'raw_payload' => ['label' => $branch->code, 'amount' => 10],
            'errors' => [],
        ]);

        return $batch->fresh();
    }

    public function test_listing_returns_only_rows_owned_by_the_callers_branch(): void
    {
        $financeA = $this->user($this->branchA, RoleCode::FINANCE_OFFICER->value);
        $financeB = $this->user($this->branchB, RoleCode::FINANCE_OFFICER->value);

        $batchA = $this->batchFor($this->branchA, $financeA);
        $this->batchFor($this->branchB, $financeB);

        Sanctum::actingAs($financeA);
        $response = $this->getJson('/api/v1/imports/rows')->assertOk();

        $rows = $response->json('data.data') ?? $response->json('data');
        $this->assertCount(1, $rows);
        $this->assertSame($batchA->id, (int) $rows[0]['import_batch_id']);
    }

    public function test_filtering_by_another_branches_batch_id_returns_nothing(): void
    {
        $financeA = $this->user($this->branchA, RoleCode::FINANCE_OFFICER->value);
        $financeB = $this->user($this->branchB, RoleCode::FINANCE_OFFICER->value);

        $this->batchFor($this->branchA, $financeA);
        $batchB = $this->batchFor($this->branchB, $financeB);

        Sanctum::actingAs($financeA);
        $response = $this->getJson("/api/v1/imports/rows?import_batch_id={$batchB->id}")->assertOk();

        $rows = $response->json('data.data') ?? $response->json('data');
        $this->assertSame([], $rows);
    }

    public function test_user_without_a_branch_sees_nothing(): void
    {
        $financeA = $this->user($this->branchA, RoleCode::FINANCE_OFFICER->value);
        $this->batchFor($this->branchA, $financeA);

        $orphan = $this->user(null, RoleCode::FINANCE_OFFICER->value);

        Sanctum::actingAs($orphan);
        $response = $this->getJson('/api/v1/imports/rows')->assertOk();

        $rows = $response->json('data.data') ?? $response->json('data');
        $this->assertSame([], $rows);
    }

    public function test_cross_branch_roles_see_every_branch(): void
    {
        $financeA = $this->user($this->branchA, RoleCode::FINANCE_OFFICER->value);
        $financeB = $this->user($this->branchB, RoleCode::FINANCE_OFFICER->value);
        $this->batchFor($this->branchA, $financeA);
        $this->batchFor($this->branchB, $financeB);

        foreach ([RoleCode::SUPER_ADMIN->value, RoleCode::AUDITOR->value] as $roleCode) {
            Sanctum::actingAs($this->user(null, $roleCode));
            $response = $this->getJson('/api/v1/imports/rows')->assertOk();
            $rows = $response->json('data.data') ?? $response->json('data');
            $this->assertCount(2, $rows, "Expected {$roleCode} to see both branches.");
        }
    }

    public function test_alternate_import_routes_reject_cross_branch_access(): void
    {
        $financeA = $this->user($this->branchA, RoleCode::FINANCE_OFFICER->value);
        $financeB = $this->user($this->branchB, RoleCode::FINANCE_OFFICER->value);

        $batchB = $this->batchFor($this->branchB, $financeB);
        $sheetB = $batchB->sheets()->first();
        $rowB = $batchB->rows()->first();

        Sanctum::actingAs($financeA);

        $this->getJson("/api/v1/imports/batches/{$batchB->id}")->assertForbidden();
        $this->getJson("/api/v1/imports/rows/{$rowB->id}")->assertForbidden();
        $this->getJson("/api/v1/imports/batches/{$batchB->id}/sheets/{$sheetB->id}")->assertForbidden();
        $this->postJson("/api/v1/imports/batches/{$batchB->id}/map")->assertForbidden();
        $this->postJson("/api/v1/imports/batches/{$batchB->id}/validate")->assertForbidden();
        $this->postJson("/api/v1/imports/batches/{$batchB->id}/sheets", ['sheet_name' => 'X'])->assertForbidden();
        $this->postJson("/api/v1/imports/sheets/{$sheetB->id}/rows", [
            'rows' => [['row_number' => 2, 'raw_payload' => ['label' => 'x', 'amount' => 1]]],
        ])->assertForbidden();
        $this->postJson("/api/v1/imports/rows/{$rowB->id}/skip")->assertForbidden();
    }

    public function test_transferring_the_uploader_does_not_move_their_past_uploads(): void
    {
        $financeA = $this->user($this->branchA, RoleCode::FINANCE_OFFICER->value);
        $batchA = $this->batchFor($this->branchA, $financeA);

        // Officer is posted to branch B; the historical batch stays with A.
        $financeA->forceFill(['branch_id' => $this->branchB->id])->save();

        $this->assertSame($this->branchA->id, (int) $batchA->fresh()->branch_id);

        Sanctum::actingAs($financeA->fresh());
        $response = $this->getJson('/api/v1/imports/rows')->assertOk();
        $rows = $response->json('data.data') ?? $response->json('data');
        $this->assertSame([], $rows, 'A transferred officer must not carry old branch rows with them.');
    }
}
