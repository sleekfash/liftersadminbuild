<?php
namespace App\Http\Requests;

use App\Models\ImportBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreImportBatchRequest extends FormRequest
{
    /** Second authorization layer in front of the controller policy check. */
    public function authorize(): bool
    {
        return (bool) $this->user() && Gate::forUser($this->user())->allows('create', ImportBatch::class);
    }

    public function rules(): array
    {
        return [
            'filename' => ['required', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
