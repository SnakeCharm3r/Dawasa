<?php

namespace App\Http\Requests;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequisitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->department_id !== null && auth()->user()->line_manager_id !== null;
    }

    public function rules(): array
    {
        return [
            'business_entity_id' => ['required', 'integer', 'exists:business_entities,id'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'required_date' => ['required', 'date', 'after_or_equal:today'],
            'purpose' => ['required', 'string', 'max:2000'],
            'estimated_amount' => ['required', 'numeric', 'min:0.01'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.specification' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit' => ['required', 'string', 'max:100', 'not_regex:/^\s*\d+(?:[.,]\d+)?\s*$/'],
            'items.*.estimated_unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.estimated_total' => ['required', 'numeric', 'min:0.01'],
            'items.*.notes' => ['nullable', 'string'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*.file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:5120'],
            'attachments.*.is_confidential' => ['sometimes', 'boolean'],
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

            if ($requester->department_id !== (int) $this->input('department_id')) {
                $validator->errors()->add('department_id', 'The requester must belong to the selected department.');
            }

            if (! $requester->line_manager_id) {
                $validator->errors()->add('line_manager_id', 'The requester does not have a valid line manager.');
            }

            $items = $this->input('items', []);
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

            if (abs($total - (float) $this->input('estimated_amount')) > 0.01 && ! $this->filled('estimate_difference_reason')) {
                $validator->errors()->add('estimate_difference_reason', 'A justification is required when the total of items differs from the requisition estimate.');
            }
        });
    }
}
