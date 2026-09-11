<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['name' => ['sometimes', 'string', 'max:160'], 'code' => ['sometimes', 'string', 'max:30', Rule::unique('branches', 'code')->ignore($this->route('branch'))], 'address' => ['nullable', 'string', 'max:500'], 'is_active' => ['sometimes', 'boolean']]; }
}