<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreImportSheetRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['sheet_name'=>['required','string','max:255'],'title_blocks'=>['nullable','array'],'heading_map'=>['nullable','array'],'raw_payload'=>['nullable','array']]; }
}
