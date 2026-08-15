# Laravel Procurement Management System - Official Documentation

## System Overview

The Laravel Procurement Management System is a comprehensive enterprise-grade application for managing the complete procurement lifecycle from requisition initiation to final closure. The system enforces strict business rules, role-based access control, segregation of duties, and maintains immutable audit trails for all financial transactions.

**Technology Stack:**
- Laravel 12
- PHP 8.2
- MySQL Database
- Spatie Laravel Permission for role-based access control

**Key Features:**
- Multi-entity and multi-department support
- Budget management with commitment tracking
- Complete procurement workflow with approvals
- Three-way invoice matching (PO, GRN, Invoice)
- Controlled payment processing
- Procurement closure with requester confirmation
- Role-based dashboards and reporting
- Comprehensive audit trails

---

## System Architecture

### Module Structure

The system is organized into the following modules:

1. **Organization & Access Management**
2. **Budget Management**
3. **Requisition Management**
4. **Sourcing & Quotation Management**
5. **Purchase Order Management**
6. **Goods Receipt Management**
7. **Supplier Invoice Management**
8. **Payment Management**
9. **Procurement Closure**
10. **Dashboards & Reporting**

---

## Module 1: Organization & Access Management

### Controllers

#### BusinessEntityController (`App\Http\Controllers\Admin\BusinessEntityController`)
**Location:** `routes/web.php` - `admin.entities.*`

**Responsibilities:**
- CRUD operations for business entities
- Entity activation/deactivation
- Manage organizational units

**Endpoints:**
- `GET /admin/entities` - List all entities
- `POST /admin/entities` - Create new entity
- `GET /admin/entities/{businessEntity}` - Show entity details
- `PATCH /admin/entities/{businessEntity}` - Update entity
- `PATCH /admin/entities/{businessEntity}/activate` - Activate entity
- `PATCH /admin/entities/{businessEntity}/deactivate` - Deactivate entity

#### DepartmentController (`App\Http\Controllers\Admin\DepartmentController`)
**Location:** `routes/web.php` - `admin.departments.*`

**Responsibilities:**
- CRUD operations for departments
- Department activation/deactivation
- Link departments to business entities

**Endpoints:**
- `GET /admin/departments` - List all departments
- `POST /admin/departments` - Create new department
- `GET /admin/departments/{department}` - Show department details
- `PATCH /admin/departments/{department}` - Update department
- `PATCH /admin/departments/{department}/activate` - Activate department
- `PATCH /admin/departments/{department}/deactivate` - Deactivate department

#### UserController (`App\Http\Controllers\Admin\UserController`)
**Location:** `routes/web.php` - `admin.users.*`

**Responsibilities:**
- CRUD operations for users
- User activation/deactivation
- Role assignment (via Spatie)

**Endpoints:**
- `GET /admin/users` - List all users
- `POST /admin/users` - Create new user
- `GET /admin/users/{user}` - Show user details
- `PATCH /admin/users/{user}` - Update user
- `PATCH /admin/users/{user}/activate` - Activate user
- `PATCH /admin/users/{user}/deactivate` - Deactivate user

#### SupplierController (`App\Http\Controllers\Admin\SupplierController`)
**Location:** `routes/web.php` - `admin.suppliers.*`

**Responsibilities:**
- CRUD operations for suppliers
- Supplier activation/deactivation
- Supplier contact and tax information management

**Endpoints:**
- `GET /admin/suppliers` - List all suppliers
- `POST /admin/suppliers` - Create new supplier
- `GET /admin/suppliers/{supplier}` - Show supplier details
- `PATCH /admin/suppliers/{supplier}` - Update supplier
- `PATCH /admin/suppliers/{supplier}/activate` - Activate supplier
- `PATCH /admin/suppliers/{supplier}/deactivate` - Deactivate supplier

### Models
- `BusinessEntity` - Organizational units
- `Department` - Departments within entities
- `User` - System users with roles
- `Supplier` - External suppliers

### Policies
- `BusinessEntityPolicy` - Entity access control
- `DepartmentPolicy` - Department access control
- `UserPolicy` - User management permissions
- `SupplierPolicy` - Supplier management permissions

---

## Module 2: Budget Management

### Controllers

#### FinancialYearController (`App\Http\Controllers\Budget\FinancialYearController`)
**Location:** `routes/web.php` - `admin.financial-years.*`

**Responsibilities:**
- CRUD operations for financial years
- Financial year activation

**Endpoints:**
- `GET /admin/financial-years` - List financial years
- `POST /admin/financial-years` - Create financial year
- `GET /admin/financial-years/{financialYear}` - Show financial year
- `PATCH /admin/financial-years/{financialYear}` - Update financial year
- `PATCH /admin/financial-years/{financialYear}/activate` - Activate financial year

#### EntityBudgetController (`App\Http\Controllers\Budget\EntityBudgetController`)
**Location:** `routes/web.php` - `admin.entity-budgets.*`

**Responsibilities:**
- CRUD operations for entity budgets
- Budget submission for approval
- Budget transaction management
- Budget history tracking

**Endpoints:**
- `GET /admin/entity-budgets` - List entity budgets
- `POST /admin/entity-budgets` - Create entity budget
- `GET /admin/entity-budgets/{entityBudget}` - Show budget details
- `PATCH /admin/entity-budgets/{entityBudget}` - Update budget
- `POST /admin/entity-budgets/{entityBudget}/submit` - Submit for approval
- `POST /admin/entity-budgets/{entityBudget}/transactions` - Create transaction
- `GET /admin/entity-budgets/{entityBudget}/history` - View transaction history

#### BudgetApprovalController (`App\Http\Controllers\Budget\BudgetApprovalController`)
**Location:** `routes/web.php` - `admin.entity-budgets.approve/return/reject`

**Responsibilities:**
- GM approval of budgets
- Return budgets for correction
- Reject budgets

**Endpoints:**
- `POST /admin/entity-budgets/{entityBudget}/approve` - Approve budget
- `POST /admin/entity-budgets/{entityBudget}/return` - Return budget
- `POST /admin/entity-budgets/{entityBudget}/reject` - Reject budget

### Models
- `FinancialYear` - Fiscal year definitions
- `EntityBudget` - Budget allocations per entity/year
- `BudgetTransaction` - Commitment, expenditure, and adjustment records
- `BudgetApproval` - Budget approval history

### Services
- None (business logic in controllers)

### Policies
- `EntityBudgetPolicy` - Budget access and modification permissions

---

## Module 3: Requisition Management

### Controllers

#### PurchaseRequisitionController (`App\Http\Controllers\Requisition\PurchaseRequisitionController`)
**Location:** `routes/web.php` - `admin.purchase-requisitions.*`

**Responsibilities:**
- CRUD operations for purchase requisitions
- Requisition submission
- Requisition cancellation
- Mark quotations as ready

**Endpoints:**
- `GET /admin/purchase-requisitions` - List requisitions
- `POST /admin/purchase-requisitions` - Create requisition
- `GET /admin/purchase-requisitions/{purchaseRequisition}` - Show requisition
- `PATCH /admin/purchase-requisitions/{purchaseRequisition}` - Update requisition
- `POST /admin/purchase-requisitions/{purchaseRequisition}/submit` - Submit for approval
- `POST /admin/purchase-requisitions/{purchaseRequisition}/cancel` - Cancel requisition
- `POST /admin/purchase-requisitions/{purchaseRequisition}/quotations-ready` - Mark quotations ready

#### RequisitionApprovalController (`App\Http\Controllers\Requisition\RequisitionApprovalController`)
**Location:** `routes/web.php` - `admin.purchase-requisitions.approve/return/reject`

**Responsibilities:**
- Line manager approval of requisitions
- Return requisitions for correction
- Reject requisitions

**Endpoints:**
- `POST /admin/purchase-requisitions/{purchaseRequisition}/approve` - Approve requisition
- `POST /admin/purchase-requisitions/{purchaseRequisition}/return` - Return requisition
- `POST /admin/purchase-requisitions/{purchaseRequisition}/reject` - Reject requisition

#### RequisitionAttachmentController (`App\Http\Controllers\Requisition\RequisitionAttachmentController`)
**Location:** `routes/web.php` - `admin.purchase-requisitions.attachments.*`

**Responsibilities:**
- Upload requisition attachments
- View and delete attachments

**Endpoints:**
- `POST /admin/purchase-requisitions/{purchaseRequisition}/attachments` - Upload attachment
- `GET /admin/purchase-requisitions/{purchaseRequisition}/attachments/{attachment}` - View attachment
- `DELETE /admin/purchase-requisitions/{purchaseRequisition}/attachments/{attachment}` - Delete attachment

### Models
- `PurchaseRequisition` - Main requisition record
- `PurchaseRequisitionItem` - Line items in requisition
- `PurchaseRequisitionAttachment` - Supporting documents
- `RequisitionApproval` - Approval history

### Policies
- `PurchaseRequisitionPolicy` - Requisition access and modification permissions

### Form Requests
- `StorePurchaseRequisitionRequest` - Create validation
- `UpdatePurchaseRequisitionRequest` - Update validation

---

## Module 4: Sourcing & Quotation Management

### Controllers

#### SupplierQuotationController (`App\Http\Controllers\Requisition\SupplierQuotationController`)
**Location:** `routes/web.php` - `admin.supplier-quotations.*`

**Responsibilities:**
- CRUD operations for supplier quotations
- Quotation submission
- Quotation withdrawal

**Endpoints:**
- `GET /admin/supplier-quotations` - List quotations
- `POST /admin/supplier-quotations` - Create quotation
- `GET /admin/supplier-quotations/{supplierQuotation}` - Show quotation
- `PATCH /admin/supplier-quotations/{supplierQuotation}` - Update quotation
- `POST /admin/supplier-quotations/{supplierQuotation}/submit` - Submit quotation
- `POST /admin/supplier-quotations/{supplierQuotation}/withdraw` - Withdraw quotation

#### QuotationRecommendationController (`App\Http\Controllers\Requisition\QuotationRecommendationController`)
**Location:** `routes/web.php` - `admin.quotation-recommendations.*` and `admin.purchase-requisitions.quotation-recommendations.*`

**Responsibilities:**
- Create quotation recommendations
- Compare quotations
- Submit recommendations for GM approval
- Update recommendations

**Endpoints:**
- `GET /admin/quotation-recommendations` - List recommendations
- `GET /admin/quotation-recommendations/{quotationRecommendation}` - Show recommendation
- `POST /admin/purchase-requisitions/{purchaseRequisition}/quotation-recommendations` - Create recommendation
- `PATCH /admin/purchase-requisitions/{purchaseRequisition}/quotation-recommendations/{quotationRecommendation}` - Update recommendation
- `POST /admin/purchase-requisitions/{purchaseRequisition}/quotation-recommendations/{quotationRecommendation}/submit` - Submit for GM approval
- `GET /admin/purchase-requisitions/{purchaseRequisition}/quotation-comparison` - Compare quotations

#### FinalApprovalController (`App\Http\Controllers\Requisition\FinalApprovalController`)
**Location:** `routes/web.php` - `admin.quotation-recommendations.approve/return/reject`

**Responsibilities:**
- GM final approval of quotation recommendations
- Return recommendations to sourcing
- Reject recommendations

**Endpoints:**
- `POST /admin/quotation-recommendations/{quotationRecommendation}/approve` - Approve recommendation
- `POST /admin/quotation-recommendations/{quotationRecommendation}/return` - Return to sourcing
- `POST /admin/quotation-recommendations/{quotationRecommendation}/reject` - Reject recommendation

### Models
- `SupplierQuotation` - Supplier quotation records
- `SupplierQuotationItem` - Quotation line items
- `QuotationRecommendation` - Procurement officer recommendations
- `ProcurementApproval` - GM approval history

### Policies
- `SupplierQuotationPolicy` - Quotation access permissions
- `QuotationRecommendationPolicy` - Recommendation access permissions

### Form Requests
- `StoreSupplierQuotationRequest` - Create validation
- `UpdateSupplierQuotationRequest` - Update validation

---

## Module 5: Purchase Order Management

### Controllers

#### PurchaseOrderController (`App\Http\Controllers\Requisition\PurchaseOrderController`)
**Location:** `routes/web.php` - `admin.purchase-orders.*`

**Responsibilities:**
- CRUD operations for purchase orders
- Submit PO for accountant confirmation
- Issue PO to supplier
- Cancel PO
- Supplier acknowledgment

**Endpoints:**
- `GET /admin/purchase-orders` - List POs
- `POST /admin/purchase-orders` - Create PO
- `GET /admin/purchase-orders/{purchaseOrder}` - Show PO
- `PATCH /admin/purchase-orders/{purchaseOrder}` - Update PO
- `POST /admin/purchase-orders/{purchaseOrder}/submit` - Submit for confirmation
- `POST /admin/purchase-orders/{purchaseOrder}/issue` - Issue PO
- `POST /admin/purchase-orders/{purchaseOrder}/cancel` - Cancel PO
- `POST /admin/purchase-orders/{purchaseOrder}/acknowledge` - Supplier acknowledgment

#### PurchaseOrderConfirmationController (`App\Http\Controllers\Requisition\PurchaseOrderConfirmationController`)
**Location:** `routes/web.php` - `admin.purchase-orders.confirm/return`

**Responsibilities:**
- Accountant budget confirmation
- Return PO to procurement for correction

**Endpoints:**
- `POST /admin/purchase-orders/{purchaseOrder}/confirm` - Confirm budget
- `POST /admin/purchase-orders/{purchaseOrder}/return` - Return to procurement

### Models
- `PurchaseOrder` - Main PO record
- `PurchaseOrderItem` - PO line items
- `PurchaseOrderApproval` - PO approval history

### Policies
- `PurchaseOrderPolicy` - PO access and modification permissions

### Form Requests
- `StorePurchaseOrderRequest` - Create validation
- `UpdatePurchaseOrderRequest` - Update validation

---

## Module 6: Goods Receipt Management

### Controllers

#### GoodsReceiptNoteController (`App\Http\Controllers\Requisition\GoodsReceiptNoteController`)
**Location:** `routes/web.php` - `admin.goods-receipt-notes.*`

**Responsibilities:**
- CRUD operations for GRNs
- Submit GRN for inspection
- Cancel GRN

**Endpoints:**
- `GET /admin/goods-receipt-notes` - List GRNs
- `POST /admin/goods-receipt-notes` - Create GRN
- `GET /admin/goods-receipt-notes/{goodsReceiptNote}` - Show GRN
- `PATCH /admin/goods-receipt-notes/{goodsReceiptNote}` - Update GRN
- `POST /admin/goods-receipt-notes/{goodsReceiptNote}/submit` - Submit for inspection
- `POST /admin/goods-receipt-notes/{goodsReceiptNote}/cancel` - Cancel GRN

#### GoodsReceiptInspectionController (`App\Http\Controllers\Requisition\GoodsReceiptInspectionController`)
**Location:** `routes/web.php` - `admin.goods-receipt-notes.inspect`

**Responsibilities:**
- GM inspection and approval of GRNs
- Accept/reject goods

**Endpoints:**
- `POST /admin/goods-receipt-notes/{goodsReceiptNote}/inspect` - Inspect and approve/reject

### Models
- `GoodsReceiptNote` - Main GRN record
- `GoodsReceiptNoteItem` - GRN line items
- `GoodsReceiptApproval` - Inspection approval history

### Services
- `GoodsReceiptService` - GRN business logic including PO status updates

### Policies
- `GoodsReceiptNotePolicy` - GRN access permissions

### Form Requests
- `StoreGoodsReceiptNoteRequest` - Create validation
- `UpdateGoodsReceiptNoteRequest` - Update validation

---

## Module 7: Supplier Invoice Management

### Controllers

#### SupplierInvoiceController (`App\Http\Controllers\Requisition\SupplierInvoiceController`)
**Location:** `routes/web.php` - `admin.supplier-invoices.*`

**Responsibilities:**
- CRUD operations for supplier invoices
- Submit invoices for matching
- Cancel invoices

**Endpoints:**
- `GET /admin/supplier-invoices` - List invoices
- `POST /admin/supplier-invoices` - Create invoice
- `GET /admin/supplier-invoices/{supplierInvoice}` - Show invoice
- `PATCH /admin/supplier-invoices/{supplierInvoice}` - Update invoice
- `POST /admin/supplier-invoices/{supplierInvoice}/submit` - Submit for matching
- `POST /admin/supplier-invoices/{supplierInvoice}/cancel` - Cancel invoice

#### InvoiceMatchingController (`App\Http\Controllers\Requisition\InvoiceMatchingController`)
**Location:** `routes/web.php` - `admin.supplier-invoices.match/variance-decision`

**Responsibilities:**
- Perform three-way matching (PO, GRN, Invoice)
- GM variance decisions

**Endpoints:**
- `POST /admin/supplier-invoices/{supplierInvoice}/match` - Perform three-way match
- `POST /admin/supplier-invoices/{supplierInvoice}/variance-decision` - GM variance decision

### Models
- `SupplierInvoice` - Main invoice record
- `SupplierInvoiceItem` - Invoice line items
- `InvoiceMatchRecord` - Three-way match results

### Services
- `ThreeWayMatchingService` - Three-way matching business logic

### Policies
- `SupplierInvoicePolicy` - Invoice access permissions

### Form Requests
- `StoreSupplierInvoiceRequest` - Create validation
- `UpdateSupplierInvoiceRequest` - Update validation
- `MatchSupplierInvoiceRequest` - Match validation
- `InvoiceVarianceDecisionRequest` - Variance decision validation

---

## Module 8: Payment Management

### Controllers

#### PaymentVoucherController (`App\Http\Controllers\Requisition\PaymentVoucherController`)
**Location:** `routes/web.php` - `admin.payment-vouchers.*`

**Responsibilities:**
- CRUD operations for payment vouchers
- Submit vouchers for GM approval
- Cancel vouchers

**Endpoints:**
- `GET /admin/payment-vouchers` - List vouchers
- `POST /admin/payment-vouchers` - Create voucher
- `GET /admin/payment-vouchers/{paymentVoucher}` - Show voucher
- `PATCH /admin/payment-vouchers/{paymentVoucher}` - Update voucher
- `POST /admin/payment-vouchers/{paymentVoucher}/submit` - Submit for approval
- `POST /admin/payment-vouchers/{paymentVoucher}/cancel` - Cancel voucher

#### PaymentApprovalController (`App\Http\Controllers\Requisition\PaymentApprovalController`)
**Location:** `routes/web.php` - `admin.payment-vouchers.decision`

**Responsibilities:**
- GM approval/return/rejection of payment vouchers

**Endpoints:**
- `POST /admin/payment-vouchers/{paymentVoucher}/decision` - GM decision (approve/return/reject)

#### PaymentController (`App\Http\Controllers\Requisition\PaymentController`)
**Location:** `routes/web.php` - `admin.payment-vouchers.record-payment`

**Responsibilities:**
- Record actual payment details
- Update budget transactions

**Endpoints:**
- `POST /admin/payment-vouchers/{paymentVoucher}/record-payment` - Record payment

### Models
- `PaymentVoucher` - Main voucher record
- `PaymentApproval` - Payment approval history

### Services
- `PaymentService` - Payment business logic including budget updates

### Policies
- `PaymentVoucherPolicy` - Voucher access permissions

### Form Requests
- `StorePaymentVoucherRequest` - Create validation
- `UpdatePaymentVoucherRequest` - Update validation
- `SubmitPaymentVoucherRequest` - Submit validation
- `PaymentVoucherDecisionRequest` - Decision validation
- `RecordPaymentRequest` - Payment recording validation

---

## Module 9: Procurement Closure

### Controllers

#### ProcurementClosureController (`App\Http\Controllers\Requisition\ProcurementClosureController`)
**Location:** `routes/web.php` - `admin.procurement-closures.*`

**Responsibilities:**
- CRUD operations for closure records
- Submit for requester confirmation
- Final closure
- Closure with exception
- Cancel closure

**Endpoints:**
- `GET /admin/procurement-closures` - List closures
- `POST /admin/procurement-closures` - Create closure draft
- `GET /admin/procurement-closures/{procurementClosure}` - Show closure
- `PATCH /admin/procurement-closures/{procurementClosure}` - Update closure
- `POST /admin/procurement-closures/{procurementClosure}/submit` - Submit for requester confirmation
- `POST /admin/procurement-closures/{procurementClosure}/close` - Final closure
- `POST /admin/procurement-closures/{procurementClosure}/close-with-exception` - Close with exception
- `POST /admin/procurement-closures/{procurementClosure}/cancel` - Cancel closure

#### RequesterClosureConfirmationController (`App\Http\Controllers\Requisition\RequesterClosureConfirmationController`)
**Location:** `routes/web.php` - `admin.procurement-closures.requester-decision`

**Responsibilities:**
- Requester confirmation of receipt/satisfaction
- Return closure for resolution

**Endpoints:**
- `POST /admin/procurement-closures/{procurementClosure}/requester-decision` - Requester decision (confirm/return)

### Models
- `ProcurementClosure` - Main closure record
- `ProcurementClosureApproval` - Closure approval history

### Services
- `ProcurementClosureService` - Closure business logic including eligibility validation

### Policies
- `ProcurementClosurePolicy` - Closure access permissions

### Form Requests
- `StoreProcurementClosureRequest` - Create validation
- `UpdateProcurementClosureRequest` - Update validation
- `SubmitProcurementClosureRequest` - Submit validation
- `RequesterClosureDecisionRequest` - Requester decision validation
- `CloseWithExceptionRequest` - Exception closure validation
- `CancelProcurementClosureRequest` - Cancel validation

---

## Module 10: Dashboards & Reporting

### Controllers

#### ProcurementDashboardController (`App\Http\Controllers\Requisition\ProcurementDashboardController`)
**Location:** `routes/web.php` - `admin.dashboard.*`

**Responsibilities:**
- Role-based dashboard views
- Executive dashboard (GM/Super Admin/Auditor)
- Operational dashboard (Procurement Officer)
- Finance dashboard (Accountant/GM/Auditor)
- Requester dashboard
- Auditor dashboard

**Endpoints:**
- `GET /admin/dashboard/executive` - Executive dashboard
- `GET /admin/dashboard/operational` - Operational dashboard
- `GET /admin/dashboard/finance` - Finance dashboard
- `GET /admin/dashboard/requester` - Requester dashboard
- `GET /admin/dashboard/auditor` - Auditor dashboard

#### ProcurementReportController (`App\Http\Controllers\Requisition\ProcurementReportController`)
**Location:** `routes/web.php` - `admin.reports.*`

**Responsibilities:**
- Generate 17 different report types
- Filterable by entity, department, date range
- Export-ready query structures

**Endpoints:**
- `GET /admin/reports/requisition-register` - Requisition register
- `GET /admin/reports/requisition-approval-turnaround` - Approval turnaround
- `GET /admin/reports/sourcing-quotation-comparison` - Quotation comparison
- `GET /admin/reports/non-lowest-price-recommendation` - Non-lowest price selections
- `GET /admin/reports/supplier-quotation-award` - Supplier awards
- `GET /admin/reports/purchase-order-register` - PO register
- `GET /admin/reports/purchase-order-status` - PO status report
- `GET /admin/reports/grn-inspection` - GRN inspection report
- `GET /admin/reports/supplier-invoice-variance` - Invoice variance report
- `GET /admin/reports/payment-voucher-register` - Payment voucher register
- `GET /admin/reports/outstanding-supplier-liabilities` - Outstanding liabilities
- `GET /admin/reports/budget-commitment` - Budget commitment report
- `GET /admin/reports/procurement-spend` - Procurement spend analysis
- `GET /admin/reports/procurement-cycle-time` - Cycle time report
- `GET /admin/reports/supplier-performance` - Supplier performance
- `GET /admin/reports/closure-exception` - Closure exceptions
- `GET /admin/reports/audit-timeline` - Audit timeline

### Services
- None (business logic in controllers)

### Policies
- None (policy checks in controllers)

---

## Security & Authorization

### Role-Based Access Control

The system uses Spatie Laravel Permission for role-based access control. Roles include:

- **super_admin** - Full system access
- **gm** - General Manager with approval authority
- **accountant** - Financial operations
- **procurement_officer** - Procurement operations
- **auditor** - Audit and compliance
- **department_head** - Department budget management
- **line_manager** - Requisition approval
- **requester** - Initiates requisitions

### Policies

All major entities have policies enforcing:
- View permissions based on role and entity access
- Create/update/delete restrictions
- Workflow action permissions (submit, approve, close, etc.)
- Segregation of duties enforcement

### Data Masking

Role-based data masking is enforced at the API level:
- Procurement officers cannot see budget balances
- Requesters cannot see payment details
- Financial data restricted to authorized roles

---

## Database Schema

### Key Tables

**Organization:**
- `business_entities`
- `departments`
- `users`
- `suppliers`

**Budget:**
- `financial_years`
- `entity_budgets`
- `budget_transactions`
- `budget_approvals`

**Requisition:**
- `purchase_requisitions`
- `purchase_requisition_items`
- `purchase_requisition_attachments`
- `requisition_approvals`

**Sourcing:**
- `supplier_quotations`
- `supplier_quotation_items`
- `quotation_recommendations`
- `procurement_approvals`

**Purchase Orders:**
- `purchase_orders`
- `purchase_order_items`
- `purchase_order_approvals`

**Goods Receipt:**
- `goods_receipt_notes`
- `goods_receipt_note_items`
- `goods_receipt_approvals`

**Invoices:**
- `supplier_invoices`
- `supplier_invoice_items`
- `invoice_match_records`

**Payments:**
- `payment_vouchers`
- `payment_approvals`

**Closure:**
- `procurement_closures`
- `procurement_closure_approvals`

**Audit:**
- `activity_logs`

---

## API Response Format

All API endpoints return JSON responses in the following format:

**Success Response:**
```json
{
  "message": "Operation successful",
  "data": { ... }
 OR
  "data": [ ... ]
}
```

**Error Response:**
```json
{
  "message": "Error description"
}
```

**Validation Error (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": { ... }
}
```

**Authorization Error (403):**
```json
{
  "message": "Unauthorized"
}
```

---

## Services

### GoodsReceiptService
- **Location:** `app/Services/GoodsReceiptService.php`
- **Responsibilities:**
  - Create and update GRNs
  - Submit GRNs for inspection
  - Perform inspections
  - Cancel GRNs
  - Update PO status based on GRN acceptance
  - Calculate received quantities

### ThreeWayMatchingService
- **Location:** `app/Services/ThreeWayMatchingService.php`
- **Responsibilities:**
  - Calculate invoiceable quantities
  - Perform three-way matching (PO vs GRN vs Invoice)
  - Return invoices for correction
  - Approve variances

### PaymentService
- **Location:** `app/Services/PaymentService.php`
- **Responsibilities:**
  - Generate voucher numbers
  - Create vouchers from invoices
  - Submit vouchers for approval
  - Approve/return/reject vouchers
  - Record payments
  - Update budget transactions
  - Cancel vouchers

### ProcurementClosureService
- **Location:** `app/Services/ProcurementClosureService.php`
- **Responsibilities:**
  - Validate closure eligibility
  - Identify unresolved obligations
  - Generate closure summaries
  - Create closure drafts
  - Submit for requester confirmation
  - Process requester decisions
  - Perform final closure
  - Close with exception
  - Cancel closures
  - Update PO status on closure

---

## Form Requests

All form requests include:
- Authorization checks based on user roles
- Validation rules for input data
- Custom validation messages where applicable

**Requisition:**
- StorePurchaseRequisitionRequest
- UpdatePurchaseRequisitionRequest

**Quotation:**
- StoreSupplierQuotationRequest
- UpdateSupplierQuotationRequest

**Invoice:**
- StoreSupplierInvoiceRequest
- UpdateSupplierInvoiceRequest
- MatchSupplierInvoiceRequest
- InvoiceVarianceDecisionRequest
- CancelSupplierInvoiceRequest

**Payment:**
- StorePaymentVoucherRequest
- UpdatePaymentVoucherRequest
- SubmitPaymentVoucherRequest
- PaymentVoucherDecisionRequest
- RecordPaymentRequest
- CancelPaymentVoucherRequest

**Closure:**
- StoreProcurementClosureRequest
- UpdateProcurementClosureRequest
- SubmitProcurementClosureRequest
- RequesterClosureDecisionRequest
- CloseWithExceptionRequest
- CancelProcurementClosureRequest

---

## Audit Trail

The system maintains comprehensive audit trails through:

1. **Approval Tables** - Each major entity has an approval table recording:
   - Actor ID
   - Action (created, submitted, approved, returned, rejected, etc.)
   - Comments
   - Timestamp

2. **Activity Logs** - Generic activity logging for:
   - Model changes
   - Sensitive operations
   - Export access

3. **Timestamps** - All records include:
   - created_at
   - updated_at
   - Specific action timestamps (submitted_at, approved_at, etc.)

4. **Immutable History** - Historical records cannot be deleted or modified

---

## Testing Considerations

### Critical Test Scenarios

1. **Closure Workflow:**
   - Close fully received and fully paid case
   - Block closure when PO partially received
   - Block closure when invoice unpaid
   - Block closure when voucher pending
   - Requester cannot confirm others' requisitions
   - Final closure changes PO to closed
   - No GRN/invoice/voucher after closure
   - Exception closure requires GM/Super Admin

2. **Role-Based Access:**
   - Procurement cannot see budget data
   - Requester cannot see payment details
   - GM can approve variances
   - Accountant can confirm budgets
   - Auditor can access all reports

3. **Segregation of Duties:**
   - Requester cannot perform final closure
   - Accountant cannot approve their own vouchers
   - Procurement cannot approve their own POs

4. **Data Integrity:**
   - PO cannot be issued without budget confirmation
   - Invoice cannot be paid without matching
   - Closure cannot complete without requester confirmation
   - Budget transactions update on payment

---

## Deployment Notes

### Environment Variables
Ensure `.env` file includes:
- Database connection details
- Mail configuration for notifications
- File system configuration for attachments

### Migrations
Run migrations in order:
1. Organization tables
2. Budget tables
3. Requisition tables
4. Sourcing tables
5. PO tables
6. GRN tables
7. Invoice tables
8. Payment tables
9. Closure tables

### Role Seeding
Seed default roles:
- super_admin
- gm
- accountant
- procurement_officer
- auditor
- department_head
- line_manager
- requester

---

## Support & Maintenance

### Common Issues

1. **Closure Eligibility Errors**
   - Check PO status is fully_received
   - Verify all invoices are paid/cancelled
   - Ensure no pending payment vouchers

2. **Budget Commitment Errors**
   - Verify financial year is active
   - Check entity budget is approved
   - Ensure sufficient allocated amount

3. **Matching Errors**
   - Verify PO has GRNs
   - Check quantities match
   - Review variance approval status

### Performance Optimization

- Use eager loading to avoid N+1 queries
- Implement database indexes on foreign keys
- Use pagination for large datasets
- Cache dashboard aggregations where appropriate

---

## Version History

- **Phase 1-7**: Organization, Budget, Requisition, Sourcing, PO, GRN modules
- **Phase 8**: Invoice, Payment modules with three-way matching
- **Phase 9**: Closure, Dashboards, Reporting modules

---

## Contact & Support

For system issues or questions, contact the development team or refer to the PROCESS_FLOW.md document for detailed workflow information.
