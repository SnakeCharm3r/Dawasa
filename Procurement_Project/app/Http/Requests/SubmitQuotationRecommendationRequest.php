<?php

namespace App\Http\Requests;

use App\Http\Requests\CeoAwareFormRequest;

class SubmitQuotationRecommendationRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'selected_quotation_id' => ['sometimes', 'integer', 'exists:supplier_quotations,id'],
            'reason_for_selection' => ['sometimes', 'string', 'max:2000'],
            'non_lowest_price_reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
