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

    protected function sameBranch(User $user, mixed $model): bool
    {
        if ($this->isCrossBranch($user)) return true;
        $userBranch = (int)($user->branch_id ?? 0);
        if (!$userBranch) return false;
        $modelBranch = (int)(is_object($model) ? ($model->branch_id ?? 0) : 0);
        return $userBranch === $modelBranch;
    }
}
