<?php
namespace App\Policies\Concerns;

use App\Enums\RoleCode;
use App\Models\User;

trait ScopesByBranch
{
    protected function isCrossBranch(User $user): bool
    {
        return method_exists($user, 'hasAnyRole') && $user->hasAnyRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::SUB_ADMIN->value,
            RoleCode::AUDITOR->value,
        ]);
    }

    /**
     * Branch staff may list records, but the controller MUST scope the query to
     * their own branch (see App\Support\ScopesQueryByBranch).
     */
    protected function canList(User $user): bool
    {
        return $this->isCrossBranch($user) || (int)($user->branch_id ?? 0) > 0;
    }

    protected function sameBranch(User $user, mixed $model): bool
    {
        if ($this->isCrossBranch($user)) return true;
        $userBranch = (int)($user->branch_id ?? 0);
        if (!$userBranch) return false;
        $modelBranch = (int)(is_object($model) ? ($model->branch_id ?? 0) : 0);
        return $userBranch === $modelBranch;
    }
}
