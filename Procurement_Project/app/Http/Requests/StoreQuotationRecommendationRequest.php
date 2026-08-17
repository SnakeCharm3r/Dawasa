<?php

namespace App\Http\Requests;

use App\Models\SupplierQuotation;
use App\Http\Requests\CeoAwareFormRequest;

class StoreQuotationRecommendationRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'selected_quotation_id' => ['required', 'integer', 'exists:supplier_quotations,id'],
            'reason_for_selection' => ['required', 'string', 'max:2000'],
            'non_lowest_price_reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $quotationId = $this->input('selected_quotation_id');

            if (! $quotationId) {
                return;
            }

            $quotation = SupplierQuotation::find($quotationId);

            if (! $quotation) {
                return;
            }

            $lowest = SupplierQuotation::query()
                ->where('purchase_requisition_id', $quotation->purchase_requisition_id)
                ->valid()
                ->orderBy('total_amount')
                ->value('total_amount');

            if ($lowest !== null && (float) $quotation->total_amount > (float) $lowest && empty($this->input('non_lowest_price_reason'))) {
                $validator->errors()->add('non_lowest_price_reason', 'A reason is required when the selected quotation is not the lowest valid quotation.');
            }
        });
    }
}
