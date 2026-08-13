<?php
namespace App\Http\Requests;

use App\Enums\RoleCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBranchHandoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasAnyRole([RoleCode::SUPER_ADMIN->value, RoleCode::SUB_ADMIN->value]);
    }

    public function rules(): array
    {
        return [
            'outgoing_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'incoming_user_id' => ['required', 'integer', 'exists:users,id'],
        ];
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
