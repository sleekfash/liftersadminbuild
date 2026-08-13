<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DisputeBranchHandoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:10', 'max:5000']];
    }
}
