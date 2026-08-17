<?php

namespace App\Http\Requests;

use App\Models\PurchaseRequisition;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Http\Requests\CeoAwareFormRequest;

class SubmitPurchaseRequisitionRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        $requisition = $this->route('purchaseRequisition');

        return auth()->check()
            && auth()->id() === $requisition->requester_id
            && in_array($requisition->status, [PurchaseRequisition::STATUS_DRAFT, PurchaseRequisition::STATUS_RETURNED], true);
    }

    public function rules(): array
    {
        return [
            'budget_shortfall_acknowledged' => ['sometimes', 'boolean'],
            'budget_shortfall_reason' => ['nullable', 'string', 'max:2000', 'required_if:budget_shortfall_acknowledged,true'],
        ];
    }
}
