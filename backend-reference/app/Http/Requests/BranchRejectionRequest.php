<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRejectionRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('reason'))) $this->merge(['reason'=>trim($this->input('reason'))]);
    }
    public static function validationRules(): array
    {
        return [
            'category'=>['required','string',Rule::in(['INCOMPLETE_DOCUMENTATION','MEMBER_ELIGIBILITY','AMOUNT_PURPOSE_MISMATCH','DUPLICATE_REQUEST','OTHER'])],
            'reason'=>['required','string','min:15','max:1000'],
        ];
    }
    public function rules(): array { return self::validationRules(); }
}