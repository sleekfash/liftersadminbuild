<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['first_name'=>['sometimes','string'],'last_name'=>['sometimes','string'],'phone'=>['nullable','string'],'email'=>['nullable','email'],'bank_name'=>['nullable','string'],'bank_account_number'=>['nullable','string'],'id_document_ref'=>['nullable','string']]; }
}
