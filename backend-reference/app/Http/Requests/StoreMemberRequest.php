<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['branch_id'=>['required','integer','exists:branches,id'],'umid'=>['required','string','regex:/^UMID-\\d{4}-\\d{6}$/','unique:members,umid'],'first_name'=>['required','string','max:100'],'last_name'=>['required','string','max:100'],'phone'=>['nullable','string','max:50'],'email'=>['nullable','email','max:150'],'bank_name'=>['nullable','string'],'bank_account_number'=>['nullable','string'],'id_document_ref'=>['nullable','string']]; }
}
