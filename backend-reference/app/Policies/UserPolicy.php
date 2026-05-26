<?php
namespace App\Policies;
use App\Enums\RoleCode; use App\Models\User;
class UserPolicy
{
    public function before(User $user,string $ability): ?bool { return $user->hasRole(RoleCode::SUPER_ADMIN->value) ? true : null; }
    public function viewAny(User $user): bool { return true; }
    public function view(User $user,mixed $model): bool { return true; }
    public function create(User $user): bool { return $user->hasAnyRole([RoleCode::SUB_ADMIN->value,RoleCode::BRANCH_MANAGER->value,RoleCode::FINANCE_OFFICER->value]); }
    public function update(User $user,mixed $model): bool { return true; }
    public function delete(User $user,mixed $model): bool { return false; }
    public function verify(User $user,mixed $model): bool { return $user->hasRole(RoleCode::BRANCH_MANAGER->value); }
    public function activate(User $user,mixed $model): bool { return false; }
    public function suspend(User $user,mixed $model): bool { return false; }
    public function reactivate(User $user,mixed $model): bool { return false; }
    public function terminate(User $user,mixed $model): bool { return false; }
    public function submit(User $user,mixed $model): bool { return true; }
    public function branchApprove(User $user,mixed $model): bool { return $user->hasRole(RoleCode::BRANCH_MANAGER->value); }
    public function financeReview(User $user,mixed $model): bool { return $user->hasRole(RoleCode::FINANCE_OFFICER->value); }
    public function authorize(User $user,mixed $model): bool { return false; }
    public function markPaid(User $user,mixed $model): bool { return $user->hasRole(RoleCode::FINANCE_OFFICER->value); }
    public function review(User $user,mixed $model): bool { return $user->hasRole(RoleCode::FINANCE_OFFICER->value); }
    public function close(User $user,mixed $model): bool { return false; }
    public function lock(User $user,mixed $model): bool { return false; }
    public function reopen(User $user,mixed $model): bool { return false; }
    public function override(User $user,mixed $model): bool { return false; }
    public function post(User $user,mixed $model): bool { return $user->hasRole(RoleCode::FINANCE_OFFICER->value); }
}
