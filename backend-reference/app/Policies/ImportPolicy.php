<?php
namespace App\Policies;

use App\Enums\RoleCode;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\ImportSheetSnapshot;
use App\Models\User;
use App\Policies\Concerns\ScopesByBranch;

/**
 * Import authorization.
 *
 * Every ability that receives a model performs BOTH a role check and a branch
 * check. Cross-branch access is available only to SUPER_ADMIN, SUB_ADMIN and
 * AUDITOR (see ScopesByBranch::isCrossBranch), or to a finance officer that a
 * super admin has explicitly granted temporary cross-branch import review via
 * the `imports.cross_branch_reviewers` app setting.
 */
class ImportPolicy
{
    use ScopesByBranch;

    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole(RoleCode::SUPER_ADMIN->value) ? true : null;
    }

    /** Resolve the owning branch id of any import-graph model. */
    private function ownerBranchId(mixed $model): ?int
    {
        $batch = match (true) {
            $model instanceof ImportBatch => $model,
            $model instanceof ImportSheetSnapshot => $model->batch,
            $model instanceof ImportRow => $model->batch,
            default => is_object($model) && isset($model->batch) ? $model->batch : null,
        };

        if (!$batch) {
            // Fail closed for anything we cannot attribute to a batch.
            return null;
        }

        // The batch owns its branch for life; the uploader's *current* branch is
        // only a fallback for legacy rows created before the denormalisation.
        $branchId = (int) ($batch->branch_id ?? 0);
        if ($branchId) return $branchId;

        $uploader = $batch->uploader;
        $legacy = (int) ($uploader->branch_id ?? 0);
        return $legacy ?: null;
    }

    private function sameImportBranch(User $user, mixed $model): bool
    {
        if ($this->isCrossBranch($user) || $this->hasCrossBranchImportGrant($user)) return true;

        $userBranch = (int) ($user->branch_id ?? 0);
        $ownerBranch = $this->ownerBranchId($model);

        return $userBranch > 0 && $ownerBranch !== null && $userBranch === $ownerBranch;
    }

    /** Super-admin-issued, audited grant of temporary cross-branch import review. */
    private function hasCrossBranchImportGrant(User $user): bool
    {
        try {
            $raw = \App\Models\AppSetting::query()
                ->where('key', 'imports.cross_branch_reviewers')
                ->value('value');
        } catch (\Throwable) {
            return false;
        }

        if (!$raw) return false;

        $ids = array_filter(array_map('intval', preg_split('/[,\s]+/', (string) $raw)));

        return in_array((int) $user->id, $ids, true)
            && $user->hasAnyRole([RoleCode::FINANCE_OFFICER->value, RoleCode::AUDITOR->value]);
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::SUB_ADMIN->value,
            RoleCode::FINANCE_OFFICER->value,
            RoleCode::AUDITOR->value,
        ]);
    }

    public function view(User $user, mixed $model): bool
    {
        return $this->viewAny($user) && $this->sameImportBranch($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            RoleCode::SUB_ADMIN->value,
            RoleCode::BRANCH_MANAGER->value,
            RoleCode::FINANCE_OFFICER->value,
        ]) && ((int) ($user->branch_id ?? 0) > 0 || $this->isCrossBranch($user));
    }

    public function update(User $user, mixed $model): bool
    {
        return $user->hasAnyRole([
            RoleCode::SUB_ADMIN->value,
            RoleCode::BRANCH_MANAGER->value,
            RoleCode::FINANCE_OFFICER->value,
        ]) && $this->sameImportBranch($user, $model);
    }

    public function delete(User $user, mixed $model): bool { return false; }

    public function verify(User $user, mixed $model): bool
    {
        return $user->hasRole(RoleCode::BRANCH_MANAGER->value) && $this->sameImportBranch($user, $model);
    }

    public function submit(User $user, mixed $model): bool
    {
        return $this->update($user, $model);
    }

    public function branchApprove(User $user, mixed $model): bool
    {
        return $user->hasRole(RoleCode::BRANCH_MANAGER->value) && $this->sameImportBranch($user, $model);
    }

    public function financeReview(User $user, mixed $model): bool
    {
        return $user->hasRole(RoleCode::FINANCE_OFFICER->value) && $this->sameImportBranch($user, $model);
    }

    public function review(User $user, mixed $model): bool
    {
        return $this->financeReview($user, $model);
    }

    public function markPaid(User $user, mixed $model): bool
    {
        return $user->hasRole(RoleCode::FINANCE_OFFICER->value) && $this->sameImportBranch($user, $model);
    }

    /** Posting an import to the trial balance is finance-only AND branch-scoped. */
    public function post(User $user, mixed $model): bool
    {
        return $user->hasRole(RoleCode::FINANCE_OFFICER->value) && $this->sameImportBranch($user, $model);
    }

    public function activate(User $user, mixed $model): bool { return false; }
    public function suspend(User $user, mixed $model): bool { return false; }
    public function reactivate(User $user, mixed $model): bool { return false; }
    public function terminate(User $user, mixed $model): bool { return false; }
    public function authorize(User $user, mixed $model): bool { return false; }
    public function close(User $user, mixed $model): bool { return false; }
    public function lock(User $user, mixed $model): bool { return false; }
    public function reopen(User $user, mixed $model): bool { return false; }
    public function override(User $user, mixed $model): bool { return false; }
}
