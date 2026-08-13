<?php
namespace App\Http\Requests;

use App\Models\ImportBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class PostTrialBalanceImportRequest extends FormRequest
{
    /** Finance-only AND branch-scoped, re-checked ahead of the controller. */
    public function authorize(): bool
    {
        $batch = $this->route('batch');
        if (!$this->user() || !$batch instanceof ImportBatch) return false;

        return Gate::forUser($this->user())->allows('post', $batch);
    }

    public function rules(): array
    {
        return [
            'monthly_period_id' => ['required', 'integer', 'exists:monthly_periods,id'],
            'source' => ['nullable', 'string', 'max:255'],
        ];
    }
}
