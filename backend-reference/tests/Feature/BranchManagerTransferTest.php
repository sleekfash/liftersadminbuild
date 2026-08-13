<?php
namespace Tests\Feature;

use App\Enums\HandoverStatus;
use App\Enums\RoleCode;
use App\Models\Branch;
use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * A posting moves the officer only. The branch portfolio is a separate record
 * set and must be byte-for-byte unchanged after a transfer.
 */
class BranchManagerTransferTest extends TestCase
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

    private function member(Branch $branch): Member
    {
        return Member::create([
            'branch_id' => $branch->id,
            'umid' => uniqid('UM'),
            'first_name' => 'A',
            'last_name' => 'B',
            'status' => 'PENDING',
        ]);
    }

    public function test_posting_a_manager_moves_only_the_officer(): void
    {
        $admin = $this->user(null, RoleCode::SUPER_ADMIN->value);
        $manager = $this->user($this->branchA, RoleCode::BRANCH_MANAGER->value);

        $this->member($this->branchA);
        $this->member($this->branchA);
        $this->member($this->branchB);

        Sanctum::actingAs($admin);
        $this->postJson("/api/v1/branches/{$this->branchB->id}/manager", [
            'user_id' => $manager->id,
            'override_handover' => true,
            'reason' => 'Reposting',
        ])->assertCreated();

        $this->assertSame($this->branchB->id, (int) $manager->fresh()->branch_id);
        $this->assertSame($manager->id, (int) $this->branchB->fresh()->current_manager_id);

        // Portfolios untouched.
        $this->assertSame(2, Member::where('branch_id', $this->branchA->id)->count());
        $this->assertSame(1, Member::where('branch_id', $this->branchB->id)->count());
    }

    public function test_replacement_requires_a_completed_handover_unless_overridden(): void
    {
        $admin = $this->user(null, RoleCode::SUB_ADMIN->value);
        $incumbent = $this->user($this->branchA, RoleCode::BRANCH_MANAGER->value);
        $successor = $this->user($this->branchB, RoleCode::BRANCH_MANAGER->value);

        Sanctum::actingAs($this->user(null, RoleCode::SUPER_ADMIN->value));
        $this->postJson("/api/v1/branches/{$this->branchA->id}/manager", [
            'user_id' => $incumbent->id,
            'override_handover' => true,
        ])->assertCreated();

        // A sub admin cannot override, and there is no handover yet.
        Sanctum::actingAs($admin);
        $this->postJson("/api/v1/branches/{$this->branchA->id}/manager", [
            'user_id' => $successor->id,
        ])->assertStatus(422);

        $handover = $this->postJson("/api/v1/branches/{$this->branchA->id}/handovers", [
            'outgoing_user_id' => $incumbent->id,
            'incoming_user_id' => $successor->id,
        ])->assertCreated()->json('data');

        $id = $handover['id'];
        $this->assertNotEmpty($handover['snapshot']);

        // Only the named parties may sign their own side.
        Sanctum::actingAs($successor);
        $this->postJson("/api/v1/branches/{$this->branchA->id}/handovers/{$id}/sign-outgoing", ['consent' => true])
            ->assertForbidden();

        Sanctum::actingAs($incumbent);
        $this->postJson("/api/v1/branches/{$this->branchA->id}/handovers/{$id}/sign-outgoing", ['consent' => true])
            ->assertOk();

        Sanctum::actingAs($successor);
        $this->postJson("/api/v1/branches/{$this->branchA->id}/handovers/{$id}/acknowledge", ['consent' => true])
            ->assertOk();

        Sanctum::actingAs($admin);
        $approved = $this->postJson("/api/v1/branches/{$this->branchA->id}/handovers/{$id}/approve")->assertOk();
        $this->assertSame(HandoverStatus::COMPLETED->value, $approved->json('data.status'));

        $this->postJson("/api/v1/branches/{$this->branchA->id}/manager", [
            'user_id' => $successor->id,
            'branch_handover_id' => $id,
        ])->assertCreated();

        $this->assertSame($successor->id, (int) $this->branchA->fresh()->current_manager_id);
        $this->assertSame($this->branchA->id, (int) $successor->fresh()->branch_id);
    }

    public function test_branch_staff_cannot_post_managers(): void
    {
        $manager = $this->user($this->branchA, RoleCode::BRANCH_MANAGER->value);
        $other = $this->user($this->branchB, RoleCode::BRANCH_MANAGER->value);

        Sanctum::actingAs($manager);
        $this->postJson("/api/v1/branches/{$this->branchA->id}/manager", ['user_id' => $other->id])
            ->assertForbidden();
    }
}
