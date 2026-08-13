<?php
namespace App\Http\Requests;

use App\Enums\RoleCode;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PostBranchManagerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return (bool) $user?->hasAnyRole([RoleCode::SUPER_ADMIN->value, RoleCode::SUB_ADMIN->value]);
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'branch_handover_id' => ['nullable', 'integer', 'exists:branch_handovers,id'],
            'override_handover' => ['sometimes', 'boolean'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            // Only a SUPER_ADMIN may bypass the handover requirement.
            if ($this->boolean('override_handover') && !$this->user()?->hasRole(RoleCode::SUPER_ADMIN->value)) {
                $v->errors()->add('override_handover', 'Only a super admin may override the handover requirement.');
            }

            $incoming = User::find($this->integer('user_id'));
            if ($incoming && !$incoming->is_active) {
                $v->errors()->add('user_id', 'Cannot post an inactive officer.');
            }
        });
    }
}
