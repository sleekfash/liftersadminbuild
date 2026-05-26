<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreImportBatchRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['filename'=>['required','string','max:255'],'metadata'=>['nullable','array']]; }
}
