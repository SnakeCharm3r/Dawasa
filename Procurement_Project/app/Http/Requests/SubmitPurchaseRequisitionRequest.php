<?php

namespace App\Http\Requests;

use App\Models\PurchaseRequisition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitPurchaseRequisitionRequest extends FormRequest
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
        return [];
    }
}
