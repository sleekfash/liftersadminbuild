<?php
namespace Tests\Unit;

use App\Enums\DisbursementStage;
use App\Enums\RoleCode;
use App\Policies\DisbursementRequestPolicy;
use App\Policies\MemberPolicy;
use App\Policies\TreasuryTransactionPolicy;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit tests around the policy branch-scope and self-approval invariants.
 * These do NOT boot Laravel — they use lightweight fakes so the suite runs on
 * any environment (including CI without a DB) and locks the security rules
 * that back the `cross_branch_create`, `permissive_policies`, and
 * `self_approval_disbursement` findings.
 */
class BranchScopeAuthorizationTest extends TestCase
{
    private function user(int $id, int $branchId, array $roles = []): object
    {
        return new class($id, $branchId, $roles) {
            public int $id;
            public int $branch_id;
            private array $roles;
            public function __construct(int $id, int $branch, array $roles)
            {
                $this->id = $id;
                $this->branch_id = $branch;
                $this->roles = $roles;
            }
            public function hasRole(string $code): bool { return in_array($code, $this->roles, true); }
            public function hasAnyRole(array $codes): bool { return (bool)array_intersect($codes, $this->roles); }
        };
    }

    private function disbursement(int $branchId, int $requestedBy, string $stage = 'SUBMITTED'): object
    {
        return (object)[
            'id' => 100,
            'branch_id' => $branchId,
            'requested_by' => $requestedBy,
            'stage' => $stage,
        ];
    }

    public function test_manager_cannot_view_disbursement_from_other_branch(): void
    {
        $policy = new DisbursementRequestPolicy();
        $manager = $this->user(1, branchId: 10, roles: [RoleCode::BRANCH_MANAGER->value]);
        $d = $this->disbursement(branchId: 20, requestedBy: 99);

        $this->assertFalse($policy->view($manager, $d));
    }

    public function test_manager_can_view_disbursement_in_own_branch(): void
    {
        $policy = new DisbursementRequestPolicy();
        $manager = $this->user(1, branchId: 10, roles: [RoleCode::BRANCH_MANAGER->value]);
        $d = $this->disbursement(branchId: 10, requestedBy: 99);

        $this->assertTrue($policy->view($manager, $d));
    }

    public function test_super_admin_bypasses_branch_scope(): void
    {
        $policy = new DisbursementRequestPolicy();
        $admin = $this->user(1, branchId: 0, roles: [RoleCode::SUPER_ADMIN->value]);
        $d = $this->disbursement(branchId: 20, requestedBy: 99);

        // `before()` short-circuits to true for SUPER_ADMIN — check direct method
        // as well as the before hook contract.
        $this->assertTrue($policy->view($admin, $d));
        $this->assertTrue($policy->before($admin, 'view'));
    }

    public function test_branch_manager_cannot_self_approve_own_submission(): void
    {
        $policy = new DisbursementRequestPolicy();
        $manager = $this->user(id: 42, branchId: 10, roles: [RoleCode::BRANCH_MANAGER->value]);
        $ownRequest = $this->disbursement(branchId: 10, requestedBy: 42);

        $this->assertFalse($policy->branchApprove($manager, $ownRequest));
    }

    public function test_branch_manager_can_approve_peer_submission_in_same_branch(): void
    {
        $policy = new DisbursementRequestPolicy();
        $manager = $this->user(id: 42, branchId: 10, roles: [RoleCode::BRANCH_MANAGER->value]);
        $peerRequest = $this->disbursement(branchId: 10, requestedBy: 43);

        $this->assertTrue($policy->branchApprove($manager, $peerRequest));
    }

    public function test_manager_cannot_approve_other_branch_even_if_not_own_request(): void
    {
        $policy = new DisbursementRequestPolicy();
        $manager = $this->user(id: 42, branchId: 10, roles: [RoleCode::BRANCH_MANAGER->value]);
        $foreign = $this->disbursement(branchId: 20, requestedBy: 43);

        $this->assertFalse($policy->branchApprove($manager, $foreign));
    }

    public function test_member_policy_blocks_cross_branch_view(): void
    {
        $policy = new MemberPolicy();
        $manager = $this->user(1, branchId: 10, roles: [RoleCode::BRANCH_MANAGER->value]);
        $foreignMember = (object)['id' => 1, 'branch_id' => 99];
        $ownMember = (object)['id' => 2, 'branch_id' => 10];

        $this->assertFalse($policy->view($manager, $foreignMember));
        $this->assertTrue($policy->view($manager, $ownMember));
    }

    public function test_treasury_policy_blocks_cross_branch_view(): void
    {
        $policy = new TreasuryTransactionPolicy();
        $officer = $this->user(1, branchId: 10, roles: [RoleCode::FINANCE_OFFICER->value]);
        $foreign = (object)['id' => 1, 'branch_id' => 99];

        // Finance officer is NOT a cross-branch role in ScopesByBranch — must be scoped.
        $this->assertFalse($policy->view($officer, $foreign));
    }

    public function test_auditor_has_read_across_branches(): void
    {
        $policy = new DisbursementRequestPolicy();
        $auditor = $this->user(1, branchId: 0, roles: [RoleCode::AUDITOR->value]);
        $foreign = $this->disbursement(branchId: 99, requestedBy: 5);

        $this->assertTrue($policy->view($auditor, $foreign));
    }

    public function test_submit_requires_same_branch_and_original_requester(): void
    {
        $policy = new DisbursementRequestPolicy();
        $manager = $this->user(id: 7, branchId: 10, roles: [RoleCode::BRANCH_MANAGER->value]);

        $ownDraft = $this->disbursement(branchId: 10, requestedBy: 7, stage: DisbursementStage::DRAFT->value);
        $peerDraft = $this->disbursement(branchId: 10, requestedBy: 8, stage: DisbursementStage::DRAFT->value);
        $foreignDraft = $this->disbursement(branchId: 20, requestedBy: 7, stage: DisbursementStage::DRAFT->value);

        $this->assertTrue($policy->submit($manager, $ownDraft));
        $this->assertFalse($policy->submit($manager, $peerDraft));
        $this->assertFalse($policy->submit($manager, $foreignDraft));
    }
}
