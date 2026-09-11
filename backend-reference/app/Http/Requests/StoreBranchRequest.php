<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['name' => ['required', 'string', 'max:160'], 'code' => ['required', 'string', 'max:30', 'unique:branches,code'], 'address' => ['nullable', 'string', 'max:500'], 'is_active' => ['sometimes', 'boolean']]; }
}