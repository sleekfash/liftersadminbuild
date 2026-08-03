<?php
namespace App\Support;

use App\Enums\RoleCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query-level branch isolation for controller index() methods.
 *
 * Policies decide *whether* a user may list a resource; this decides *which
 * rows* they see. Every index() that returns branch-owned records must apply
 * it, otherwise branch staff can paginate other branches' data.
 */
trait ScopesQueryByBranch
{
    protected function userIsCrossBranch(?User $user): bool
    {
        return (bool)($user && $user->hasAnyRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::SUB_ADMIN->value,
            RoleCode::AUDITOR->value,
        ]));
    }

    /** Restrict a query to the caller's branch unless they hold a cross-branch role. */
    protected function scopeToBranch(Builder $query, ?User $user, string $column = 'branch_id'): Builder
    {
        if ($this->userIsCrossBranch($user)) return $query;
        $branchId = (int)($user->branch_id ?? 0);
        // Fail closed: a user without a branch assignment sees nothing.
        return $branchId ? $query->where($column, $branchId) : $query->whereRaw('1 = 0');
    }
}
