<?php
namespace App\Http\Requests;

use App\Models\ImportSheetSnapshot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreImportRowsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $sheet = $this->route('sheet');
        if (!$this->user() || !$sheet instanceof ImportSheetSnapshot) return false;

        return Gate::forUser($this->user())->allows('update', $sheet);
    }

    public function rules(): array
    {
        return [
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.row_number' => ['required', 'integer', 'min:1'],
            'rows.*.raw_payload' => ['required', 'array'],
        ];
    }
}
