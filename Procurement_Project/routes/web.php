<?php

use App\Http\Controllers\Admin\BusinessEntityController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\SupplierVerificationController;
use App\Http\Controllers\Admin\SupplierPerformanceController;
use App\Http\Controllers\Admin\TenderController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Budget\BudgetApprovalController;
use App\Http\Controllers\Budget\EntityBudgetController;
use App\Http\Controllers\Budget\FinancialYearController;
use App\Http\Controllers\Requisition\FinalApprovalController;
use App\Http\Controllers\Requisition\GoodsReceiptInspectionController;
use App\Http\Controllers\Requisition\GoodsReceiptNoteController;
use App\Http\Controllers\Requisition\InvoiceMatchingController;
use App\Http\Controllers\Requisition\PaymentApprovalController;
use App\Http\Controllers\Requisition\PaymentController;
use App\Http\Controllers\Requisition\PaymentVoucherController;
use App\Http\Controllers\Requisition\ProcurementClosureController;
use App\Http\Controllers\Requisition\ProcurementDashboardController;
use App\Http\Controllers\Requisition\ProcurementReportController;
use App\Http\Controllers\Requisition\PurchaseOrderConfirmationController;
use App\Http\Controllers\Requisition\PurchaseOrderController;
use App\Http\Controllers\Requisition\PurchaseRequisitionController;
use App\Http\Controllers\Requisition\QuotationRecommendationController;
use App\Http\Controllers\Requisition\RequesterClosureConfirmationController;
use App\Http\Controllers\Requisition\RequisitionApprovalController;
use App\Http\Controllers\Requisition\RequisitionAttachmentController;
use App\Http\Controllers\Requisition\SupplierInvoiceController;
use App\Http\Controllers\Requisition\SupplierQuotationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\PublicTenderController;
use App\Http\Controllers\Portal\SupplierPortalController;
use App\Http\Controllers\Portal\SupplierRegistrationController;
use App\Http\Controllers\Portal\TenderResponseController;
use App\Http\Controllers\Portal\SupplierEmailVerificationController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('tenders', [PublicTenderController::class, 'index'])->name('tenders.index');
    Route::get('tenders/{tenderNumber}', [PublicTenderController::class, 'show'])->name('tenders.show');
    Route::get('supplier-categories', [PublicTenderController::class, 'categories'])->name('categories.index');
    Route::post('supplier-registration', [SupplierRegistrationController::class, 'store'])->middleware('throttle:5,1')->name('supplier-registration');
});

Route::middleware('auth')->prefix('supplier-portal')->name('supplier-portal.')->group(function () {
    Route::post('email/verification-notification', [SupplierEmailVerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.send');
    Route::get('dashboard', [SupplierPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('profile', [SupplierPortalController::class, 'profile'])->name('profile');
    Route::patch('profile', [SupplierPortalController::class, 'updateProfile'])->name('profile.update');
    Route::get('documents', [SupplierPortalController::class, 'documents'])->name('documents.index');
    Route::post('documents', [SupplierPortalController::class, 'storeDocument'])->name('documents.store');
    Route::get('documents/{document}/download', [SupplierPortalController::class, 'downloadDocument'])->name('documents.download');
    Route::get('tenders', [SupplierPortalController::class, 'tenders'])->name('tenders.index');
    Route::get('tenders/{tender}', [SupplierPortalController::class, 'showTender'])->name('tenders.show');
    Route::get('tender-responses', [TenderResponseController::class, 'index'])->name('responses.index');
    Route::post('tenders/{tender}/responses', [TenderResponseController::class, 'store'])->name('responses.store');
    Route::get('tender-responses/{tenderResponse}', [TenderResponseController::class, 'show'])->name('responses.show');
    Route::patch('tender-responses/{tenderResponse}', [TenderResponseController::class, 'update'])->name('responses.update');
    Route::post('tender-responses/{tenderResponse}/submit', [TenderResponseController::class, 'submit'])->name('responses.submit');
    Route::post('tender-responses/{tenderResponse}/documents', [TenderResponseController::class, 'storeDocument'])->name('response-documents.store');
    Route::get('tender-response-documents/{document}/download', [TenderResponseController::class, 'downloadDocument'])->name('response-documents.download');
});

Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('csrf', [AuthController::class, 'csrf'])->name('csrf');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login');
    Route::get('demo-users', [AuthController::class, 'demoUsers'])->name('demo-users');
    Route::post('demo-login', [AuthController::class, 'demoLogin'])->middleware('throttle:30,1')->name('demo-login');
    Route::get('me', [AuthController::class, 'me'])->middleware('auth')->name('me');
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
});

Route::get('email/verify/{id}/{hash}', [SupplierEmailVerificationController::class, 'verify'])
    ->middleware(['auth', 'signed'])->name('verification.verify');

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('entities', BusinessEntityController::class)
        ->parameters(['entities' => 'businessEntity'])
        ->except(['create', 'edit']);

    Route::patch('entities/{businessEntity}/activate', [BusinessEntityController::class, 'activate'])->name('entities.activate');
    Route::patch('entities/{businessEntity}/deactivate', [BusinessEntityController::class, 'deactivate'])->name('entities.deactivate');

    Route::resource('departments', DepartmentController::class)->except(['create', 'edit']);
    Route::patch('departments/{department}/activate', [DepartmentController::class, 'activate'])->name('departments.activate');
    Route::patch('departments/{department}/deactivate', [DepartmentController::class, 'deactivate'])->name('departments.deactivate');

    Route::resource('users', UserController::class)->except(['create', 'edit']);
    Route::patch('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
    Route::patch('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');

    Route::resource('suppliers', SupplierController::class)->except(['create', 'edit']);
    Route::patch('suppliers/{supplier}/activate', [SupplierController::class, 'activate'])->name('suppliers.activate');
    Route::patch('suppliers/{supplier}/deactivate', [SupplierController::class, 'deactivate'])->name('suppliers.deactivate');
    Route::get('supplier-applications', [SupplierVerificationController::class, 'applications'])->name('supplier-applications.index');
    Route::post('suppliers/{supplier}/verification-decision', [SupplierVerificationController::class, 'decision'])->name('suppliers.verification-decision');
    Route::post('suppliers/{supplier}/preferred', [SupplierVerificationController::class, 'preferred'])->name('suppliers.preferred');
    Route::post('supplier-documents/{document}/decision', [SupplierVerificationController::class, 'documentDecision'])->name('supplier-documents.decision');
    Route::get('supplier-documents/{document}/download', [SupplierVerificationController::class, 'download'])->name('supplier-documents.download');
    Route::get('supplier-compliance-alerts', [SupplierPerformanceController::class, 'alerts'])->name('suppliers.compliance-alerts');
    Route::get('suppliers/{supplier}/performance', [SupplierPerformanceController::class, 'show'])->name('suppliers.performance');
    Route::post('suppliers/{supplier}/performance/calculate', [SupplierPerformanceController::class, 'calculate'])->name('suppliers.performance.calculate');
    Route::post('suppliers/{supplier}/performance/incidents', [SupplierPerformanceController::class, 'incident'])->name('suppliers.performance.incidents');
    Route::post('supplier-performance-incidents/{incident}/resolve', [SupplierPerformanceController::class, 'resolveIncident'])->name('suppliers.performance.incidents.resolve');
    Route::post('suppliers/{supplier}/performance/override', [SupplierPerformanceController::class, 'override'])->name('suppliers.performance.override');

    Route::apiResource('tenders', TenderController::class)->except(['destroy']);
    Route::post('tenders/{tender}/submit-publication', [TenderController::class, 'submitPublication'])->name('tenders.submit-publication');
    Route::post('tenders/{tender}/publish', [TenderController::class, 'publish'])->name('tenders.publish');
    Route::post('tenders/{tender}/invite', [TenderController::class, 'invite'])->name('tenders.invite');
    Route::post('tenders/{tender}/close', [TenderController::class, 'close'])->name('tenders.close');
    Route::post('tenders/{tender}/cancel', [TenderController::class, 'cancel'])->name('tenders.cancel');
    Route::get('tenders/{tender}/responses', [TenderController::class, 'responses'])->name('tenders.responses');
    Route::post('tender-responses/{tenderResponse}/compliance', [TenderController::class, 'compliance'])->name('tender-responses.compliance');
    Route::get('tender-response-documents/{document}/download', [TenderController::class, 'downloadResponseDocument'])->name('tender-response-documents.download');

    Route::resource('financial-years', FinancialYearController::class)
        ->parameters(['financial-years' => 'financialYear'])
        ->except(['create', 'edit']);
    Route::patch('financial-years/{financialYear}/activate', [FinancialYearController::class, 'activate'])->name('financial-years.activate');

    Route::resource('entity-budgets', EntityBudgetController::class)
        ->parameters(['entity-budgets' => 'entityBudget'])
        ->except(['create', 'edit']);
    Route::post('entity-budgets/{entityBudget}/submit', [EntityBudgetController::class, 'submit'])->name('entity-budgets.submit');
    Route::post('entity-budgets/{entityBudget}/transactions', [EntityBudgetController::class, 'storeTransaction'])->name('entity-budgets.transactions.store');
    Route::get('entity-budgets/{entityBudget}/history', [EntityBudgetController::class, 'history'])->name('entity-budgets.history');

    Route::get('requisition-budget-check', [PurchaseRequisitionController::class, 'budgetCheck'])->name('purchase-requisitions.budget-check');

    Route::resource('purchase-requisitions', PurchaseRequisitionController::class)
        ->parameters(['purchase-requisitions' => 'purchaseRequisition'])
        ->except(['create', 'edit']);

    Route::post('purchase-requisitions/{purchaseRequisition}/submit', [PurchaseRequisitionController::class, 'submit'])->name('purchase-requisitions.submit');
    Route::post('purchase-requisitions/{purchaseRequisition}/cancel', [PurchaseRequisitionController::class, 'cancel'])->name('purchase-requisitions.cancel');
    Route::post('purchase-requisitions/{purchaseRequisition}/quotations-ready', [PurchaseRequisitionController::class, 'markQuotationsReady'])->name('purchase-requisitions.quotations-ready');

    Route::post('purchase-requisitions/{purchaseRequisition}/attachments', [RequisitionAttachmentController::class, 'store'])->name('purchase-requisitions.attachments.store');
    Route::get('purchase-requisitions/{purchaseRequisition}/attachments/{attachment}', [RequisitionAttachmentController::class, 'show'])->name('purchase-requisitions.attachments.show');
    Route::delete('purchase-requisitions/{purchaseRequisition}/attachments/{attachment}', [RequisitionAttachmentController::class, 'destroy'])->name('purchase-requisitions.attachments.destroy');

    Route::apiResource('supplier-quotations', SupplierQuotationController::class)
        ->parameters(['supplier-quotations' => 'supplierQuotation'])
        ->except(['destroy']);

    Route::post('supplier-quotations/{supplierQuotation}/submit', [SupplierQuotationController::class, 'submit'])->name('supplier-quotations.submit');
    Route::post('supplier-quotations/{supplierQuotation}/withdraw', [SupplierQuotationController::class, 'withdraw'])->name('supplier-quotations.withdraw');
    Route::post('supplier-quotations/{supplierQuotation}/reject', [SupplierQuotationController::class, 'reject'])->name('supplier-quotations.reject');
    Route::post('supplier-quotations/{supplierQuotation}/request-approval', [SupplierQuotationController::class, 'requestApproval'])->name('supplier-quotations.request-approval');

    Route::post('purchase-requisitions/{purchaseRequisition}/approve', [RequisitionApprovalController::class, 'approve'])->name('purchase-requisitions.approve');
    Route::post('purchase-requisitions/{purchaseRequisition}/return', [RequisitionApprovalController::class, 'return'])->name('purchase-requisitions.return');
    Route::post('purchase-requisitions/{purchaseRequisition}/reject', [RequisitionApprovalController::class, 'reject'])->name('purchase-requisitions.reject');

    Route::get('quotation-recommendations', [QuotationRecommendationController::class, 'index'])->name('quotation-recommendations.index');
    Route::get('quotation-recommendations/{quotationRecommendation}', [QuotationRecommendationController::class, 'show'])->name('quotation-recommendations.show');
    Route::post('purchase-requisitions/{purchaseRequisition}/quotation-recommendations', [QuotationRecommendationController::class, 'createRecommendation'])->name('purchase-requisitions.quotation-recommendations.store');
    Route::patch('purchase-requisitions/{purchaseRequisition}/quotation-recommendations/{quotationRecommendation}', [QuotationRecommendationController::class, 'update'])->name('purchase-requisitions.quotation-recommendations.update');
    Route::post('purchase-requisitions/{purchaseRequisition}/quotation-recommendations/{quotationRecommendation}/submit', [QuotationRecommendationController::class, 'submit'])->name('purchase-requisitions.quotation-recommendations.submit');
    Route::get('purchase-requisitions/{purchaseRequisition}/quotation-comparison', [QuotationRecommendationController::class, 'compare'])->name('purchase-requisitions.quotation-comparison');

    Route::apiResource('purchase-orders', PurchaseOrderController::class)
        ->parameters(['purchase-orders' => 'purchaseOrder'])
        ->except(['destroy']);

    Route::post('purchase-orders/{purchaseOrder}/submit', [PurchaseOrderController::class, 'submitForConfirmation'])->name('purchase-orders.submit');
    Route::post('purchase-orders/{purchaseOrder}/issue', [PurchaseOrderController::class, 'issue'])->name('purchase-orders.issue');
    Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::post('purchase-orders/{purchaseOrder}/acknowledge', [PurchaseOrderController::class, 'acknowledge'])->name('purchase-orders.acknowledge');

    Route::post('purchase-orders/{purchaseOrder}/confirm', [PurchaseOrderConfirmationController::class, 'confirm'])->name('purchase-orders.confirm');
    Route::post('purchase-orders/{purchaseOrder}/return', [PurchaseOrderConfirmationController::class, 'returnToProcurement'])->name('purchase-orders.return');
    Route::post('purchase-orders/{purchaseOrder}/reject', [PurchaseOrderConfirmationController::class, 'reject'])->name('purchase-orders.reject');

    Route::apiResource('goods-receipt-notes', GoodsReceiptNoteController::class)
        ->parameters(['goods-receipt-notes' => 'goodsReceiptNote'])
        ->except(['destroy']);

    Route::post('goods-receipt-notes/{goodsReceiptNote}/submit', [GoodsReceiptNoteController::class, 'submit'])->name('goods-receipt-notes.submit');
    Route::post('goods-receipt-notes/{goodsReceiptNote}/cancel', [GoodsReceiptNoteController::class, 'cancel'])->name('goods-receipt-notes.cancel');
    Route::post('goods-receipt-notes/{goodsReceiptNote}/inspect', [GoodsReceiptInspectionController::class, 'inspect'])->name('goods-receipt-notes.inspect');

    Route::apiResource('supplier-invoices', SupplierInvoiceController::class)
        ->parameters(['supplier-invoices' => 'supplierInvoice'])
        ->except(['destroy']);

    Route::post('supplier-invoices/{supplierInvoice}/submit', [SupplierInvoiceController::class, 'submit'])->name('supplier-invoices.submit');
    Route::post('supplier-invoices/{supplierInvoice}/cancel', [SupplierInvoiceController::class, 'cancel'])->name('supplier-invoices.cancel');

    Route::post('supplier-invoices/{supplierInvoice}/match', [InvoiceMatchingController::class, 'match'])->name('supplier-invoices.match');
    Route::post('supplier-invoices/{supplierInvoice}/variance-decision', [InvoiceMatchingController::class, 'varianceDecision'])->name('supplier-invoices.variance-decision');

    Route::apiResource('payment-vouchers', PaymentVoucherController::class)
        ->parameters(['payment-vouchers' => 'paymentVoucher'])
        ->except(['destroy']);

    Route::post('payment-vouchers/{paymentVoucher}/submit', [PaymentVoucherController::class, 'submit'])->name('payment-vouchers.submit');
    Route::post('payment-vouchers/{paymentVoucher}/cancel', [PaymentVoucherController::class, 'cancel'])->name('payment-vouchers.cancel');

    Route::post('payment-vouchers/{paymentVoucher}/decision', [PaymentApprovalController::class, 'decision'])->name('payment-vouchers.decision');
    Route::post('payment-vouchers/{paymentVoucher}/record-payment', [PaymentController::class, 'record'])->name('payment-vouchers.record-payment');

    Route::apiResource('procurement-closures', ProcurementClosureController::class)
        ->parameters(['procurement-closures' => 'procurementClosure'])
        ->except(['destroy']);

    Route::post('procurement-closures/{procurementClosure}/submit', [ProcurementClosureController::class, 'submitForRequesterConfirmation'])->name('procurement-closures.submit');
    Route::post('procurement-closures/{procurementClosure}/close', [ProcurementClosureController::class, 'close'])->name('procurement-closures.close');
    Route::post('procurement-closures/{procurementClosure}/close-with-exception', [ProcurementClosureController::class, 'closeWithException'])->name('procurement-closures.close-with-exception');
    Route::post('procurement-closures/{procurementClosure}/cancel', [ProcurementClosureController::class, 'cancelDraft'])->name('procurement-closures.cancel');

    Route::post('procurement-closures/{procurementClosure}/requester-decision', [RequesterClosureConfirmationController::class, 'decision'])->name('procurement-closures.requester-decision');

    Route::get('dashboard/executive', [ProcurementDashboardController::class, 'executive'])->name('dashboard.executive');
    Route::get('dashboard/operational', [ProcurementDashboardController::class, 'operational'])->name('dashboard.operational');
    Route::get('dashboard/finance', [ProcurementDashboardController::class, 'finance'])->name('dashboard.finance');
    Route::get('dashboard/requester', [ProcurementDashboardController::class, 'requester'])->name('dashboard.requester');
    Route::get('dashboard/auditor', [ProcurementDashboardController::class, 'auditor'])->name('dashboard.auditor');

    Route::get('reports/requisition-register', [ProcurementReportController::class, 'requisitionRegister'])->name('reports.requisition-register');
    Route::get('reports/requisition-approval-turnaround', [ProcurementReportController::class, 'requisitionApprovalTurnaround'])->name('reports.requisition-approval-turnaround');
    Route::get('reports/sourcing-quotation-comparison', [ProcurementReportController::class, 'sourcingQuotationComparison'])->name('reports.sourcing-quotation-comparison');
    Route::get('reports/non-lowest-price-recommendation', [ProcurementReportController::class, 'nonLowestPriceRecommendation'])->name('reports.non-lowest-price-recommendation');
    Route::get('reports/supplier-quotation-award', [ProcurementReportController::class, 'supplierQuotationAward'])->name('reports.supplier-quotation-award');
    Route::get('reports/purchase-order-register', [ProcurementReportController::class, 'purchaseOrderRegister'])->name('reports.purchase-order-register');
    Route::get('reports/purchase-order-status', [ProcurementReportController::class, 'purchaseOrderStatusReport'])->name('reports.purchase-order-status');
    Route::get('reports/grn-inspection', [ProcurementReportController::class, 'grnInspectionReport'])->name('reports.grn-inspection');
    Route::get('reports/supplier-invoice-variance', [ProcurementReportController::class, 'supplierInvoiceVarianceReport'])->name('reports.supplier-invoice-variance');
    Route::get('reports/payment-voucher-register', [ProcurementReportController::class, 'paymentVoucherRegister'])->name('reports.payment-voucher-register');
    Route::get('reports/outstanding-supplier-liabilities', [ProcurementReportController::class, 'outstandingSupplierLiabilities'])->name('reports.outstanding-supplier-liabilities');
    Route::get('reports/budget-commitment', [ProcurementReportController::class, 'budgetCommitmentReport'])->name('reports.budget-commitment');
    Route::get('reports/procurement-spend', [ProcurementReportController::class, 'procurementSpendReport'])->name('reports.procurement-spend');
    Route::get('reports/procurement-cycle-time', [ProcurementReportController::class, 'procurementCycleTimeReport'])->name('reports.procurement-cycle-time');
    Route::get('reports/supplier-performance', [ProcurementReportController::class, 'supplierPerformanceReport'])->name('reports.supplier-performance');
    Route::get('reports/closure-exception', [ProcurementReportController::class, 'closureExceptionReport'])->name('reports.closure-exception');
    Route::get('reports/audit-timeline', [ProcurementReportController::class, 'auditTimeline'])->name('reports.audit-timeline');

    Route::post('quotation-recommendations/{quotationRecommendation}/approve', [FinalApprovalController::class, 'approve'])->name('quotation-recommendations.approve');
    Route::post('quotation-recommendations/{quotationRecommendation}/return', [FinalApprovalController::class, 'returnToSourcing'])->name('quotation-recommendations.return');
    Route::post('quotation-recommendations/{quotationRecommendation}/reject', [FinalApprovalController::class, 'reject'])->name('quotation-recommendations.reject');

    Route::post('entity-budgets/{entityBudget}/approve', [BudgetApprovalController::class, 'approve'])->name('entity-budgets.approve');
    Route::post('entity-budgets/{entityBudget}/return', [BudgetApprovalController::class, 'returnBudget'])->name('entity-budgets.return');
    Route::post('entity-budgets/{entityBudget}/reject', [BudgetApprovalController::class, 'reject'])->name('entity-budgets.reject');
});
