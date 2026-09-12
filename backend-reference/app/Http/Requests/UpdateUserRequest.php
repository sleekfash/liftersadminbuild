<?php
namespace App\Http\Requests;

use App\Enums\RoleCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /** Only admins may change privileged fields (roles, branch, active state). */
    public function actorIsAdmin(): bool
    {
        $actor = $this->user();
        return $actor !== null && $actor->hasAnyRole([RoleCode::SUPER_ADMIN->value, RoleCode::SUB_ADMIN->value]);
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();
        if (! $this->actorIsAdmin()) {
            unset($data['roles'], $data['branch_id'], $data['is_active']);
        }
        return is_null($key) ? $data : data_get($data, $key, $default);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->actorIsAdmin()) return;
            foreach (['roles', 'branch_id', 'is_active'] as $field) {
                if ($this->has($field)) {
                    $validator->errors()->add($field, 'You are not allowed to change this field.');
                }
            }
        });
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'email' => ['sometimes', 'email', 'max:190', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'password' => ['sometimes', 'nullable', 'string', 'min:12'],
            'branch_id' => ['sometimes', 'nullable', 'integer', 'exists:branches,id'],
            'roles' => ['sometimes', 'array', 'min:1'],
            'roles.*' => ['required', Rule::in(array_map(fn (RoleCode $role) => $role->value, RoleCode::cases()))],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}