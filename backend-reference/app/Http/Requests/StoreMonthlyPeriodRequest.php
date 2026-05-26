<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreMonthlyPeriodRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['name'=>['required','string','max:120'],'month'=>['required','integer','min:1','max:12'],'year'=>['required','integer','min:2020','max:2100'],'opening_balance'=>['nullable','numeric']]; }
}
