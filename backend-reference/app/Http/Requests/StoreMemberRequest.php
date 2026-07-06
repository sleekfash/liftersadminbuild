<?php
namespace App\Http\Requests;
use App\Enums\RoleCode;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['branch_id'=>['required','integer','exists:branches,id'],'umid'=>['required','string','regex:/^UMID-\\d{4}-\\d{6}$/','unique:members,umid'],'first_name'=>['required','string','max:100'],'last_name'=>['required','string','max:100'],'phone'=>['nullable','string','max:50'],'email'=>['nullable','email','max:150'],'bank_name'=>['nullable','string'],'bank_account_number'=>['nullable','string'],'id_document_ref'=>['nullable','string']]; }
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($v) {
            $user = $this->user();
            if (!$user) return;
            $crossBranchRoles = [RoleCode::SUPER_ADMIN->value, RoleCode::SUB_ADMIN->value];
            if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole($crossBranchRoles)) return;
            if ((int)$this->input('branch_id') !== (int)($user->branch_id ?? 0)) {
                $v->errors()->add('branch_id', 'You may only create records for your own branch.');
            }
        });
    }
}
