<?php

namespace App\Http\Requests;

use App\Models\Department;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseRequisitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->id === $this->route('purchaseRequisition')->requester_id;
    }

    public function rules(): array
    {
        return [
            'business_entity_id' => ['sometimes', 'integer', 'exists:business_entities,id'],
            'department_id' => ['sometimes', 'integer', 'exists:departments,id'],
            'required_date' => ['sometimes', 'date', 'after_or_equal:today'],
            'purpose' => ['sometimes', 'string', 'max:2000'],
            'estimated_amount' => ['sometimes', 'numeric', 'min:0.01'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.item_name' => ['required_with:items', 'string', 'max:255'],
            'items.*.specification' => ['nullable', 'string'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.01'],
            'items.*.unit' => ['required_with:items', 'string', 'max:100'],
            'items.*.estimated_unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.estimated_total' => ['required_with:items', 'numeric', 'min:0.01'],
            'items.*.notes' => ['nullable', 'string'],
            'estimate_difference_reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $department = Department::find($this->input('department_id'));
            $requester = auth()->user();

            if ($department && $department->business_entity_id !== (int) $this->input('business_entity_id')) {
                $validator->errors()->add('department_id', 'The selected department does not belong to the selected business entity.');
            }

            if ($this->filled('department_id') && $requester->department_id !== (int) $this->input('department_id')) {
                $validator->errors()->add('department_id', 'The requester must belong to the selected department.');
            }

            $items = $this->input('items', []);
            if ($items) {
                $total = 0;
                foreach ($items as $item) {
                    $estimatedUnitPrice = isset($item['estimated_unit_price']) ? (float) $item['estimated_unit_price'] : null;
                    $quantity = isset($item['quantity']) ? (float) $item['quantity'] : 0;
                    $estimatedTotal = isset($item['estimated_total']) ? (float) $item['estimated_total'] : 0;

                    if ($estimatedUnitPrice !== null) {
                        $total += $quantity * $estimatedUnitPrice;
                    } else {
                        $total += $estimatedTotal;
                    }
                }

                if ($this->filled('estimated_amount') && abs($total - (float) $this->input('estimated_amount')) > 0.01 && ! $this->filled('estimate_difference_reason')) {
                    $validator->errors()->add('estimate_difference_reason', 'A justification is required when the total of items differs from the requisition estimate.');
                }
            }
        });
    }
}
