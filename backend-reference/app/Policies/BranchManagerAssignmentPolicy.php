<?php
namespace App\Policies;

use App\Enums\RoleCode;
use App\Models\User;
use App\Policies\Concerns\ScopesByBranch;

class BranchManagerAssignmentPolicy
{
    use ScopesByBranch;

    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole(RoleCode::SUPER_ADMIN->value) ? true : null;
    }

    private function isAdmin(User $user): bool
    {
        return $user->hasAnyRole([RoleCode::SUPER_ADMIN->value, RoleCode::SUB_ADMIN->value]);
    }

    public function viewAny(User $user): bool
    {
        return $this->canList($user);
    }

    public function view(User $user, mixed $model): bool
    {
        return $this->isAdmin($user) || $this->sameBranch($user, $model);
    }

    /** Postings are an administrative act. */
    public function post(User $user, mixed $model = null): bool
    {
        return $this->isAdmin($user);
    }

    public function unassign(User $user, mixed $model = null): bool
    {
        return $this->isAdmin($user);
    }

    /** Skipping the handover requirement is SUPER_ADMIN only (see before()). */
    public function overrideHandover(User $user): bool
    {
        return false;
    }
}
