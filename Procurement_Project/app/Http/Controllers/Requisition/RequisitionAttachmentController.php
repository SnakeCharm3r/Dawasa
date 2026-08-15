<?php

namespace App\Http\Controllers\Requisition;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseRequisitionAttachmentRequest;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionAttachment;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class RequisitionAttachmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(StorePurchaseRequisitionAttachmentRequest $request, PurchaseRequisition $purchaseRequisition): JsonResponse
    {
        $this->authorize('uploadAttachment', $purchaseRequisition);

        if (! in_array($purchaseRequisition->status, [PurchaseRequisition::STATUS_DRAFT, PurchaseRequisition::STATUS_RETURNED], true)) {
            return response()->json(['message' => 'Attachments can only be uploaded to draft or returned requisitions.'], 422);
        }

        $attachment = null;
        DB::transaction(function () use ($request, $purchaseRequisition, &$attachment) {
            $file = $request->file('file');
            $path = Storage::disk('public')->putFile('purchase_requisition_attachments', $file);

            $attachment = PurchaseRequisitionAttachment::create([
                'purchase_requisition_id' => $purchaseRequisition->id,
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'is_confidential' => $request->boolean('is_confidential'),
                'uploaded_by' => Auth::id(),
            ]);

            ActivityLog::record(Auth::user(), 'purchase_requisition.attachment.uploaded', $purchaseRequisition, [], $attachment->toArray());
        });

        return response()->json(['message' => 'Attachment uploaded successfully.', 'data' => $attachment], 201);
    }

    public function show(PurchaseRequisition $purchaseRequisition, PurchaseRequisitionAttachment $attachment)
    {
        $this->authorize('view', $purchaseRequisition);

        if ($attachment->purchase_requisition_id !== $purchaseRequisition->id) {
            abort(404);
        }

        if (Auth::user()->hasRole('procurement_officer') && $attachment->is_confidential) {
            return response()->json(['message' => 'You are not authorized to view this confidential attachment.'], 403);
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->original_name, ['Content-Type' => $attachment->mime_type]);
    }

    public function destroy(Request $request, PurchaseRequisition $purchaseRequisition, PurchaseRequisitionAttachment $attachment): JsonResponse
    {
        $this->authorize('deleteAttachment', $purchaseRequisition);

        if ($attachment->purchase_requisition_id !== $purchaseRequisition->id) {
            return response()->json(['message' => 'Attachment not found.'], 404);
        }

        if (! in_array($purchaseRequisition->status, [PurchaseRequisition::STATUS_DRAFT, PurchaseRequisition::STATUS_RETURNED], true)) {
            return response()->json(['message' => 'Attachments can only be deleted from draft or returned requisitions.'], 422);
        }

        DB::transaction(function () use ($attachment, $purchaseRequisition) {
            Storage::disk('public')->delete($attachment->file_path);
            $attachment->delete();
            ActivityLog::record(Auth::user(), 'purchase_requisition.attachment.deleted', $purchaseRequisition, [], ['attachment_id' => $attachment->id]);
        });

        return response()->json(['message' => 'Attachment deleted successfully.']);
    }
}
