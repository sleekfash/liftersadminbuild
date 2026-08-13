<?php
namespace App\Policies;

use App\Enums\RoleCode;
use App\Models\BranchHandover;
use App\Models\User;
use App\Policies\Concerns\ScopesByBranch;

class BranchHandoverPolicy
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

    /** Branch staff read only their own branch's handovers. */
    public function view(User $user, mixed $model): bool
    {
        return $this->sameBranch($user, $model)
            || $this->isParty($user, $model);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /** Only the named outgoing officer signs the outgoing side. */
    public function signOutgoing(User $user, BranchHandover $handover): bool
    {
        return (int) $handover->outgoing_user_id === (int) $user->id;
    }

    /** Only the named incoming officer acknowledges. */
    public function acknowledge(User $user, BranchHandover $handover): bool
    {
        return (int) $handover->incoming_user_id === (int) $user->id;
    }

    public function dispute(User $user, BranchHandover $handover): bool
    {
        return $this->isParty($user, $handover) || $this->isAdmin($user);
    }

    public function approve(User $user, BranchHandover $handover): bool
    {
        return $this->isAdmin($user) && !$this->isParty($user, $handover);
    }

    public function delete(User $user, mixed $model): bool
    {
        return false;
    }

    private function isParty(User $user, mixed $handover): bool
    {
        if (!$handover instanceof BranchHandover) return false;
        return in_array((int) $user->id, [
            (int) $handover->outgoing_user_id,
            (int) $handover->incoming_user_id,
        ], true);
    }
}
