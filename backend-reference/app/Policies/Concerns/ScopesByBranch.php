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

    /**
     * Branch check for records with no direct branch_id column: walk a dotted
     * relation path (e.g. 'disbursementRequest' or 'run.items') to the owning
     * branch. Fails closed when the path cannot be resolved.
     */
    protected function sameBranchVia(User $user, mixed $model, string $path): bool
    {
        if ($this->isCrossBranch($user)) return true;
        $userBranch = (int)($user->branch_id ?? 0);
        if (!$userBranch || !is_object($model)) return false;

        $node = $model;
        foreach (explode('.', $path) as $segment) {
            if (!is_object($node) || !method_exists($node, $segment)) return false;
            $node = $node->{$segment};
            if ($node === null) return false;
        }
        return $userBranch === (int)($node->branch_id ?? 0);
    }
}
