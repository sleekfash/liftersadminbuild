<?php
namespace App\Http\Requests;
use App\Enums\RoleCode;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
class StoreDisbursementRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['member_id'=>['required','integer','exists:members,id'],'branch_id'=>['required','integer','exists:branches,id'],'amount'=>['required','numeric','min:1'],'purpose'=>['nullable','string','max:2000']]; }
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($v) {
            $user = $this->user();
            if (!$user) return;
            $crossBranchRoles = [RoleCode::SUPER_ADMIN->value, RoleCode::SUB_ADMIN->value];
            if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole($crossBranchRoles)) return;
            if ((int)$this->input('branch_id') !== (int)($user->branch_id ?? 0)) {
                $v->errors()->add('branch_id', 'You may only create disbursements for your own branch.');
            }
        });
    }
}
