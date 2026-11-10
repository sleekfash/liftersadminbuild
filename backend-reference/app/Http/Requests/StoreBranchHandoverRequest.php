<?php
namespace App\Http\Requests;

use App\Enums\RoleCode;
use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBranchHandoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $branch = $this->route('branch');
        if (!$user || !$branch instanceof Branch) return false;
        return $user->hasAnyRole([RoleCode::SUPER_ADMIN->value, RoleCode::SUB_ADMIN->value])
            || ($user->hasRole(RoleCode::BRANCH_MANAGER->value)
                && (int) $user->branch_id === (int) $branch->id
                && (int) $branch->current_manager_id === (int) $user->id);
    }

    public function rules(): array
    {
        $rules = [
            'incoming_user_id' => ['required', 'integer', 'exists:users,id'],
        ];

        if ($this->user()?->hasAnyRole([RoleCode::SUPER_ADMIN->value, RoleCode::SUB_ADMIN->value])) {
            $rules['outgoing_user_id'] = ['nullable', 'integer', 'exists:users,id'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($this->filled('outgoing_user_id')
                && $this->integer('outgoing_user_id') === $this->integer('incoming_user_id')) {
                $v->errors()->add('incoming_user_id', 'Outgoing and incoming officers must differ.');
            }
        });
    }
}
