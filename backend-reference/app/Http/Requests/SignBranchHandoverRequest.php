<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SignBranchHandoverRequest extends FormRequest
{
    /** Party-level authorization is enforced by BranchHandoverPolicy. */
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:5000'],
            'consent' => ['required', 'accepted'],
        ];
    }
}
