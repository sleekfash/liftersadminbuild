<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreImportRowsRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['rows'=>['required','array','min:1'],'rows.*.row_number'=>['required','integer','min:1'],'rows.*.raw_payload'=>['required','array']]; }
}
