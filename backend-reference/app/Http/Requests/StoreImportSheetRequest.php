<?php
namespace App\Http\Requests;

use App\Models\ImportBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreImportSheetRequest extends FormRequest
{
    /** Branch ownership of the parent batch is re-checked here. */
    public function authorize(): bool
    {
        $batch = $this->route('batch');
        if (!$this->user() || !$batch instanceof ImportBatch) return false;

        return Gate::forUser($this->user())->allows('update', $batch);
    }

    public function rules(): array
    {
        return [
            'sheet_name' => ['required', 'string', 'max:255'],
            'title_blocks' => ['nullable', 'array'],
            'heading_map' => ['nullable', 'array'],
            'raw_payload' => ['nullable', 'array'],
        ];
    }
}
