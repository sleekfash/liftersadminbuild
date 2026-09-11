<?php
namespace App\Http\Requests;

use App\Enums\RoleCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', Rule::in(array_map(fn (RoleCode $role) => $role->value, RoleCode::cases()))],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}