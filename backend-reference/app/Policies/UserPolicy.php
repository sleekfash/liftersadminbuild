<?php
namespace App\Policies;
use App\Enums\RoleCode; use App\Models\User;
class UserPolicy
{
    public function before(User $user,string $ability): ?bool { return $user->hasRole(RoleCode::SUPER_ADMIN->value) ? true : null; }
    private function isAdmin(User $user): bool { return $user->hasAnyRole([RoleCode::SUPER_ADMIN->value,RoleCode::SUB_ADMIN->value]); }
    private function isSelf(User $user,mixed $model): bool { return is_object($model) && (int)($model->id ?? 0) === (int)$user->id; }
    public function viewAny(User $user): bool { return $this->isAdmin($user); }
    public function view(User $user,mixed $model): bool { return $this->isAdmin($user) || $this->isSelf($user,$model); }
    public function create(User $user): bool { return $this->isAdmin($user); }
    public function update(User $user,mixed $model): bool { return $this->isAdmin($user) || $this->isSelf($user,$model); }
    public function delete(User $user,mixed $model): bool { return false; }
    public function verify(User $user,mixed $model): bool { return $this->isAdmin($user); }
    public function activate(User $user,mixed $model): bool { return false; }
    public function suspend(User $user,mixed $model): bool { return false; }
    public function reactivate(User $user,mixed $model): bool { return false; }
    public function terminate(User $user,mixed $model): bool { return false; }
    public function submit(User $user,mixed $model): bool { return $this->isSelf($user,$model); }
    public function branchApprove(User $user,mixed $model): bool { return $this->isAdmin($user); }
    public function financeReview(User $user,mixed $model): bool { return $this->isAdmin($user); }
    public function authorize(User $user,mixed $model): bool { return false; }
    public function markPaid(User $user,mixed $model): bool { return false; }
    public function review(User $user,mixed $model): bool { return $this->isAdmin($user); }
    public function close(User $user,mixed $model): bool { return false; }
    public function lock(User $user,mixed $model): bool { return false; }
    public function reopen(User $user,mixed $model): bool { return false; }
    public function override(User $user,mixed $model): bool { return false; }
    public function post(User $user,mixed $model): bool { return false; }
}
