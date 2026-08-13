<?php
namespace App\Policies;

use App\Enums\RoleCode;
use App\Models\User;
use App\Policies\Concerns\ScopesByBranch;

class BranchPolicy
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

    public function viewAny(User $user): bool { return $this->canList($user); }

    /** Branch staff read only their own branch; the id column is the branch key. */
    public function view(User $user, mixed $model): bool
    {
        if ($this->isCrossBranch($user)) return true;
        return (int)($user->branch_id ?? 0) > 0 && (int)($user->branch_id) === (int)($model->id ?? 0);
    }

    public function create(User $user): bool { return $this->isAdmin($user); }
    public function update(User $user, mixed $model): bool { return $this->isAdmin($user); }
    public function delete(User $user, mixed $model): bool { return false; }
}
