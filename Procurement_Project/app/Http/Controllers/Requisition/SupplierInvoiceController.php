<?php

namespace App\Http\Controllers\Requisition;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelSupplierInvoiceRequest;
use App\Http\Requests\StoreSupplierInvoiceRequest;
use App\Http\Requests\SubmitSupplierInvoiceRequest;
use App\Http\Requests\UpdateSupplierInvoiceRequest;
use App\Models\GoodsReceiptNote;
use App\Models\GoodsReceiptNoteItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierInvoice;
use App\Models\SupplierInvoiceItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierInvoiceController extends Controller
{
    protected function loadRelations(SupplierInvoice $invoice): void
    {
        $invoice->load([
            'purchaseOrder',
            'supplier',
            'businessEntity',
            'financialYear',
            'submittedBy',
            'approvedBy',
            'items.purchaseOrderItem',
            'matchRecords.matchedBy',
            'paymentVouchers',
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SupplierInvoice::class);

        $query = SupplierInvoice::with(['purchaseOrder.requisition', 'purchaseOrder.selectedQuotation', 'supplier', 'businessEntity', 'submittedBy']);

        if ($request->has('po_number')) {
            $query->whereHas('purchaseOrder', fn ($q) => $q->where('purchase_order_number', 'like', '%'.$request->input('po_number').'%'));
        }

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }

        if ($request->has('business_entity_id')) {
            $query->where('business_entity_id', $request->input('business_entity_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('from_date')) {
            $query->where('invoice_date', '>=', $request->input('from_date'));
        }

        if ($request->has('to_date')) {
            $query->where('invoice_date', '<=', $request->input('to_date'));
        }

        if ($request->has('due_date_from')) {
            $query->where('due_date', '>=', $request->input('due_date_from'));
        }

        if ($request->has('due_date_to')) {
            $query->where('due_date', '<=', $request->input('due_date_to'));
        }

        $invoices = $query->orderByDesc('invoice_date')->paginate($request->input('per_page', 15));

        return response()->json(['data' => $invoices]);
    }

    public function show(SupplierInvoice $supplierInvoice): JsonResponse
    {
        $this->authorize('view', $supplierInvoice);

        $this->loadRelations($supplierInvoice);

        return response()->json(['data' => $supplierInvoice]);
    }

    public function store(StoreSupplierInvoiceRequest $request): JsonResponse
    {
        $this->authorize('create', SupplierInvoice::class);

        $po = PurchaseOrder::findOrFail($request->input('purchase_order_id'));

        $hasAcceptedDelivery = GoodsReceiptNote::where('purchase_order_id', $po->id)
            ->whereIn('status', [
                GoodsReceiptNote::STATUS_ACCEPTED,
                GoodsReceiptNote::STATUS_PARTIALLY_ACCEPTED,
            ])
            ->exists();

        if (! $hasAcceptedDelivery) {
            return response()->json([
                'message' => 'An invoice can only be recorded after the store or warehouse accepts a delivery against the LPO.',
            ], 422);
        }

        return DB::transaction(function () use ($po, $request) {
            $invoice = SupplierInvoice::create([
                'invoice_number' => $request->input('invoice_number'),
                'supplier_id' => $po->supplier_id,
                'purchase_order_id' => $po->id,
                'business_entity_id' => $po->business_entity_id,
                'financial_year_id' => $po->financial_year_id,
                'invoice_date' => $request->input('invoice_date'),
                'due_date' => $request->input('due_date'),
                'received_date' => $request->input('received_date'),
                'currency' => $request->input('currency', 'TZS'),
                'subtotal' => $request->input('subtotal'),
                'discount_amount' => $request->input('discount_amount', 0),
                'tax_amount' => $request->input('tax_amount', 0),
                'total_amount' => $request->input('total_amount'),
                'matched_amount' => 0,
                'paid_amount' => 0,
                'outstanding_amount' => $request->input('total_amount'),
                'status' => SupplierInvoice::STATUS_DRAFT,
                'notes' => $request->input('notes'),
            ]);

            foreach ($request->input('items') as $itemData) {
                $poItem = PurchaseOrderItem::findOrFail($itemData['purchase_order_item_id']);
                if ($poItem->purchase_order_id !== $po->id) {
                    throw ValidationException::withMessages([
                        'items' => ['Every invoice line must belong to the selected LPO.'],
                    ]);
                }

                $previouslyInvoiced = SupplierInvoiceItem::where('purchase_order_item_id', $poItem->id)
                    ->sum('quantity_invoiced');
                $acceptedQuantity = GoodsReceiptNoteItem::where('purchase_order_item_id', $poItem->id)
                    ->sum('quantity_accepted');
                $invoiceableQuantity = max(0, (float) $acceptedQuantity - (float) $previouslyInvoiced);

                if ((float) $itemData['quantity_invoiced'] > $invoiceableQuantity) {
                    throw ValidationException::withMessages([
                        'items' => ["Invoice quantity for {$poItem->item_name} exceeds the accepted, uninvoiced store quantity."],
                    ]);
                }

                SupplierInvoiceItem::create([
                    'supplier_invoice_id' => $invoice->id,
                    'purchase_order_item_id' => $poItem->id,
                    'item_name' => $poItem->item_name,
                    'specification' => $poItem->specification,
                    'quantity_invoiced' => $itemData['quantity_invoiced'],
                    'quantity_previously_invoiced' => $previouslyInvoiced,
                    'quantity_accepted' => $itemData['quantity_invoiced'],
                    'unit' => $poItem->unit,
                    'unit_price' => $itemData['unit_price'],
                    'line_total' => $itemData['quantity_invoiced'] * $itemData['unit_price'],
                ]);
            }

            $this->loadRelations($invoice);

            return response()->json([
                'message' => 'Supplier invoice draft created successfully.',
                'data' => $invoice,
            ], 201);
        });
    }

    public function update(UpdateSupplierInvoiceRequest $request, SupplierInvoice $supplierInvoice): JsonResponse
    {
        $this->authorize('update', $supplierInvoice);

        return DB::transaction(function () use ($supplierInvoice, $request) {
            $supplierInvoice->update($request->except('items'));

            if ($request->has('items')) {
                foreach ($request->input('items') as $itemData) {
                    $invoiceItem = SupplierInvoiceItem::findOrFail($itemData['id']);
                    $invoiceItem->update([
                        'quantity_invoiced' => $itemData['quantity_invoiced'],
                        'quantity_accepted' => $itemData['quantity_invoiced'],
                        'unit_price' => $itemData['unit_price'],
                        'line_total' => $itemData['quantity_invoiced'] * $itemData['unit_price'],
                    ]);
                }
            }

            $supplierInvoice->outstanding_amount = (float) $supplierInvoice->total_amount - (float) $supplierInvoice->paid_amount;
            $supplierInvoice->save();

            $this->loadRelations($supplierInvoice);

            return response()->json([
                'message' => 'Supplier invoice updated successfully.',
                'data' => $supplierInvoice,
            ]);
        });
    }

    public function submit(SubmitSupplierInvoiceRequest $request, SupplierInvoice $supplierInvoice): JsonResponse
    {
        $this->authorize('submit', $supplierInvoice);

        return DB::transaction(function () use ($supplierInvoice) {
            $supplierInvoice->status = SupplierInvoice::STATUS_SUBMITTED;
            $supplierInvoice->submitted_by = Auth::id();
            $supplierInvoice->submitted_at = now();
            $supplierInvoice->save();

            $this->loadRelations($supplierInvoice);

            return response()->json([
                'message' => 'Supplier invoice submitted for matching.',
                'data' => $supplierInvoice,
            ]);
        });
    }

    public function cancel(CancelSupplierInvoiceRequest $request, SupplierInvoice $supplierInvoice): JsonResponse
    {
        $this->authorize('cancel', $supplierInvoice);

        return DB::transaction(function () use ($supplierInvoice, $request) {
            $supplierInvoice->status = SupplierInvoice::STATUS_CANCELLED;
            $supplierInvoice->rejection_reason = $request->input('cancellation_reason');
            $supplierInvoice->save();

            $this->loadRelations($supplierInvoice);

            return response()->json([
                'message' => 'Supplier invoice cancelled.',
                'data' => $supplierInvoice,
            ]);
        });
    }
}
