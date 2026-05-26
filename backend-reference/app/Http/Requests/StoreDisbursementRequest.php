<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreDisbursementRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['member_id'=>['required','integer','exists:members,id'],'branch_id'=>['required','integer','exists:branches,id'],'amount'=>['required','numeric','min:1'],'purpose'=>['nullable','string','max:2000']]; }
}
