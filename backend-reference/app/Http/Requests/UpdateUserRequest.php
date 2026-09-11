<?php
namespace App\Http\Requests;

use App\Enums\RoleCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

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