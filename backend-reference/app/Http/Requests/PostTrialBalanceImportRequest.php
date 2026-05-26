<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class PostTrialBalanceImportRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['monthly_period_id'=>['required','integer','exists:monthly_periods,id'],'source'=>['nullable','string','max:255']]; }
}
