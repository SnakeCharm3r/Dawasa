import type { AuthUser, JsonRecord, Role } from "@/lib/types";

export type Column = {
  label: string;
  key: string;
  format?: "status" | "money" | "date" | "boolean";
};

export type ActionSpec = {
  label: string;
  tone?: "primary" | "danger" | "neutral";
  path: (item: JsonRecord) => string;
  method?: "POST" | "PATCH";
  prompt?: string;
  inputLabel?: string;
  requiredInput?: boolean;
  body?: (input: string, item: JsonRecord) => JsonRecord;
  appliesTo?: (item: JsonRecord) => boolean;
  visible: (item: JsonRecord, user: AuthUser) => boolean;
};

export type ModuleConfig = {
  title: string;
  description: string;
  endpoint: string;
  searchPlaceholder: string;
  columns: Column[];
  statusOptions?: string[];
  create?: "supplier" | "requisition" | "proforma" | "receipt" | "invoice";
  actions?: ActionSpec[];
  roles?: Role[];
};

const role = (user: AuthUser, ...roles: Role[]) => user.roles.includes("ceo") || user.roles.some((value) => roles.includes(value));
const id = (item: JsonRecord) => Number(item.id);
const status = (item: JsonRecord) => String(item.status ?? item.closure_status ?? "");
const relatedId = (item: JsonRecord, key: string) => Number((item[key] as JsonRecord | undefined)?.id);
const relatedStatus = (item: JsonRecord, key: string) => String((item[key] as JsonRecord | undefined)?.status ?? "");
const budgetRequiresAcknowledgement = (item: JsonRecord) => Boolean((item.budget_check as JsonRecord | undefined)?.requires_acknowledgement);
const lineManagerId = (item: JsonRecord) => Number((item.line_manager as JsonRecord | undefined)?.id);

export const modules: Record<string, ModuleConfig> = {
  requisitions: {
    title: "Purchase requisitions",
    description: "Requests moving through a non-blocking budget check, line-manager approval, GM approval, sourcing, and award.",
    endpoint: "admin/purchase-requisitions",
    searchPlaceholder: "Search number or purpose",
    create: "requisition",
    statusOptions: ["draft", "submitted", "pending_gm_approval", "returned", "approved_for_sourcing", "quotations_ready", "pending_final_approval", "approved_for_purchase", "returned_to_sourcing", "rejected", "cancelled"],
    columns: [
      { label: "Requisition", key: "requisition_number" },
      { label: "Purpose", key: "purpose" },
      { label: "Entity", key: "business_entity.name" },
      { label: "Requester", key: "requester.name" },
      { label: "Required", key: "required_date", format: "date" },
      { label: "Status", key: "status", format: "status" },
    ],
    actions: [
      { label: "Submit", tone: "primary", prompt: "The organisation budget check is complete. Submit this requisition into its approval route? Line-manager requests go directly to the GM.", path: (item) => `admin/purchase-requisitions/${id(item)}/submit`, visible: (item, user) => ["draft", "returned"].includes(status(item)) && role(user, "requester", "line_manager") && !budgetRequiresAcknowledgement(item) },
      { label: "Proceed despite shortfall", tone: "primary", prompt: "The budget check requires funding review. The request may proceed using loan or other funding, but the reason will be recorded for approvers and audit. Line-manager requests go directly to the GM.", inputLabel: "Funding or loan justification", requiredInput: true, path: (item) => `admin/purchase-requisitions/${id(item)}/submit`, body: (input) => ({ budget_shortfall_acknowledged: true, budget_shortfall_reason: input }), visible: (item, user) => ["draft", "returned"].includes(status(item)) && role(user, "requester", "line_manager") && budgetRequiresAcknowledgement(item) },
      { label: "Line manager approve", tone: "primary", prompt: "Approve this request and forward it to the GM?", inputLabel: "Approval comments", path: (item) => `admin/purchase-requisitions/${id(item)}/approve`, body: (input) => ({ comments: input }), appliesTo: (item) => status(item) === "submitted", visible: (item, user) => status(item) === "submitted" && lineManagerId(item) === user.id },
      { label: "GM approve for sourcing", tone: "primary", prompt: "Give final requisition approval and release this request to sourcing? The latest budget position will be recorded, including any shortfall.", inputLabel: "Final approval comments", path: (item) => `admin/purchase-requisitions/${id(item)}/approve`, body: (input) => ({ comments: input }), appliesTo: (item) => status(item) === "pending_gm_approval", visible: (item, user) => status(item) === "pending_gm_approval" && role(user, "gm") },
      { label: "Return", prompt: "Return this requisition to the requester?", inputLabel: "Required corrections", path: (item) => `admin/purchase-requisitions/${id(item)}/return`, body: (input) => ({ comments: input }), appliesTo: (item) => ["submitted", "pending_gm_approval"].includes(status(item)), visible: (item, user) => (status(item) === "submitted" && lineManagerId(item) === user.id) || (status(item) === "pending_gm_approval" && role(user, "gm")) },
      { label: "Reject", tone: "danger", prompt: "Reject this requisition?", inputLabel: "Reason for rejection", path: (item) => `admin/purchase-requisitions/${id(item)}/reject`, body: (input) => ({ comments: input }), appliesTo: (item) => ["submitted", "pending_gm_approval"].includes(status(item)), visible: (item, user) => (status(item) === "submitted" && lineManagerId(item) === user.id) || (status(item) === "pending_gm_approval" && role(user, "gm")) },
      { label: "Mark quotations ready", tone: "primary", path: (item) => `admin/purchase-requisitions/${id(item)}/quotations-ready`, visible: (item, user) => ["approved_for_sourcing", "returned_to_sourcing"].includes(status(item)) && role(user, "procurement_officer", "super_admin") },
      { label: "Create LPO", tone: "primary", prompt: "Create the LPO from the approved proforma for this requisition?", path: () => "admin/purchase-orders", body: (_input, item) => ({ purchase_requisition_id: id(item) }), visible: (item, user) => status(item) === "approved_for_purchase" && role(user, "procurement_officer") },
    ],
  },
  quotations: {
    title: "Supplier proformas",
    description: "Supplier proformas associated with approved requisitions before LPO creation.",
    endpoint: "admin/supplier-quotations",
    searchPlaceholder: "Filter quotation records",
    create: "proforma",
    statusOptions: ["draft", "active", "withdrawn", "expired", "rejected"],
    columns: [
      { label: "ID", key: "id" },
      { label: "Proforma", key: "quotation_number" },
      { label: "Supplier", key: "supplier.name" },
      { label: "Requisition", key: "requisition.requisition_number" },
      { label: "Total", key: "total_amount", format: "money" },
      { label: "Valid until", key: "valid_until", format: "date" },
      { label: "Status", key: "status", format: "status" },
    ],
    actions: [
      { label: "Submit proforma", tone: "primary", path: (item) => `admin/supplier-quotations/${id(item)}/submit`, visible: (item, user) => status(item) === "draft" && role(user, "super_admin", "procurement_officer") },
      { label: "Send for approval", tone: "primary", inputLabel: "Reason for selecting this proforma", requiredInput: true, path: (item) => `admin/supplier-quotations/${id(item)}/request-approval`, body: (input) => ({ reason_for_selection: input }), visible: (item, user) => status(item) === "active" && !relatedId(item, "approval_recommendation") && role(user, "super_admin", "procurement_officer") },
      { label: "Approve proforma", tone: "primary", inputLabel: "Approval comments", path: (item) => `admin/quotation-recommendations/${relatedId(item, "approval_recommendation")}/approve`, body: (input) => ({ comments: input }), visible: (item, user) => relatedStatus(item, "approval_recommendation") === "submitted" && role(user, "gm") },
      { label: "Reject proforma", tone: "danger", inputLabel: "Reason for rejection", requiredInput: true, path: (item) => `admin/quotation-recommendations/${relatedId(item, "approval_recommendation")}/reject`, body: (input) => ({ comments: input }), visible: (item, user) => relatedStatus(item, "approval_recommendation") === "submitted" && role(user, "gm") },
      { label: "Withdraw", prompt: "Withdraw this active proforma?", path: (item) => `admin/supplier-quotations/${id(item)}/withdraw`, visible: (item, user) => status(item) === "active" && role(user, "super_admin", "procurement_officer") },
      { label: "Reject", tone: "danger", prompt: "Reject this proforma?", inputLabel: "Reason for rejection", path: (item) => `admin/supplier-quotations/${id(item)}/reject`, body: (input) => ({ reason: input }), visible: (item, user) => status(item) === "active" && role(user, "super_admin", "procurement_officer") },
    ],
  },
  "purchase-orders": {
    title: "Local purchase orders (LPOs)",
    description: "Accountant-confirmed commitments created from approved requisitions and proformas.",
    endpoint: "admin/purchase-orders",
    searchPlaceholder: "Search PO or supplier",
    statusOptions: ["draft", "pending_accountant_confirmation", "confirmed", "issued", "acknowledged", "partially_received", "fully_received", "rejected", "closed", "cancelled"],
    columns: [
      { label: "ID", key: "id" },
      { label: "LPO", key: "purchase_order_number" },
      { label: "Requisition", key: "requisition.requisition_number" },
      { label: "Proforma", key: "selected_quotation.quotation_number" },
      { label: "Supplier", key: "supplier.name" },
      { label: "Order date", key: "order_date", format: "date" },
      { label: "Delivery", key: "expected_delivery_date", format: "date" },
      { label: "Total", key: "total_amount", format: "money" },
      { label: "Status", key: "status", format: "status" },
    ],
    actions: [
      { label: "Submit for confirmation", tone: "primary", inputLabel: "Notes for accountant", path: (item) => `admin/purchase-orders/${id(item)}/submit`, body: (input) => ({ comments: input || null }), visible: (item, user) => status(item) === "draft" && role(user, "procurement_officer") },
      { label: "Confirm", tone: "primary", inputLabel: "Confirmation comments", path: (item) => `admin/purchase-orders/${id(item)}/confirm`, body: (input) => ({ comments: input || null }), visible: (item, user) => status(item) === "pending_accountant_confirmation" && role(user, "accountant") },
      { label: "Return", inputLabel: "Required corrections", path: (item) => `admin/purchase-orders/${id(item)}/return`, body: (input) => ({ comments: input }), visible: (item, user) => status(item) === "pending_accountant_confirmation" && role(user, "accountant") },
      { label: "Reject", tone: "danger", inputLabel: "Reason for rejection", path: (item) => `admin/purchase-orders/${id(item)}/reject`, body: (input) => ({ comments: input }), visible: (item, user) => status(item) === "pending_accountant_confirmation" && role(user, "accountant") },
      { label: "Issue order", tone: "primary", path: (item) => `admin/purchase-orders/${id(item)}/issue`, visible: (item, user) => status(item) === "confirmed" && role(user, "procurement_officer") },
      { label: "Acknowledge", path: (item) => `admin/purchase-orders/${id(item)}/acknowledge`, inputLabel: "Supplier acknowledgement reference", body: (input) => ({ acknowledgement_reference: input || null }), visible: (item, user) => status(item) === "issued" && role(user, "procurement_officer", "accountant", "super_admin") },
    ],
  },
  "goods-receipts": {
    title: "Delivery and store receipts",
    description: "Delivery-note signing, warehouse verification, accepted quantities, and LPO fulfilment.",
    endpoint: "admin/goods-receipt-notes",
    searchPlaceholder: "Search PO number",
    create: "receipt",
    roles: ["super_admin", "gm", "accountant", "procurement_officer", "department_head", "requester", "auditor", "storekeeper", "receiving_officer"],
    statusOptions: ["draft", "submitted", "partially_accepted", "accepted", "rejected", "cancelled"],
    columns: [
      { label: "GRN", key: "grn_number" },
      { label: "LPO", key: "purchase_order.purchase_order_number" },
      { label: "Requisition", key: "purchase_order.requisition.requisition_number" },
      { label: "Delivery note", key: "delivery_note_number" },
      { label: "Supplier", key: "supplier.name" },
      { label: "Received", key: "received_date", format: "date" },
      { label: "Received by", key: "received_by.name" },
      { label: "Status", key: "status", format: "status" },
    ],
    actions: [
      { label: "Submit for store verification", tone: "primary", path: (item) => `admin/goods-receipt-notes/${id(item)}/submit`, visible: (item, user) => status(item) === "draft" && role(user, "procurement_officer", "super_admin", "storekeeper", "receiving_officer") },
      { label: "Accept and sign", tone: "primary", inputLabel: "Store verification comments", path: (item) => `admin/goods-receipt-notes/${id(item)}/inspect`, body: (input, receipt) => ({ inspection_comments: input || null, items: ((receipt.items as JsonRecord[] | undefined) ?? []).map((line) => ({ id: Number(line.id), quantity_accepted: Number(line.quantity_received), quantity_rejected: 0, condition_status: "accepted", inspection_notes: null })) }), visible: (item, user) => status(item) === "submitted" && Number((item.received_by as JsonRecord | undefined)?.id) !== user.id && role(user, "super_admin", "department_head", "storekeeper", "receiving_officer") },
    ],
  },
  invoices: {
    title: "Supplier invoices",
    description: "Invoices moving through PO and receipt matching, variance review, and payment.",
    endpoint: "admin/supplier-invoices",
    searchPlaceholder: "Search invoice or PO",
    create: "invoice",
    statusOptions: ["draft", "submitted", "pending_match", "matched", "matched_with_variance", "approved_for_payment", "partially_paid", "paid", "cancelled"],
    columns: [
      { label: "ID", key: "id" },
      { label: "Invoice", key: "invoice_number" },
      { label: "Supplier", key: "supplier.name" },
      { label: "LPO", key: "purchase_order.purchase_order_number" },
      { label: "Requisition", key: "purchase_order.requisition.requisition_number" },
      { label: "Due date", key: "due_date", format: "date" },
      { label: "Outstanding", key: "outstanding_amount", format: "money" },
      { label: "Status", key: "status", format: "status" },
    ],
    actions: [
      { label: "Submit for matching", tone: "primary", path: (item) => `admin/supplier-invoices/${id(item)}/submit`, visible: (item, user) => status(item) === "draft" && role(user, "accountant", "super_admin") },
      { label: "Run three-way match", tone: "primary", path: (item) => `admin/supplier-invoices/${id(item)}/match`, visible: (item, user) => ["submitted", "pending_match"].includes(status(item)) && role(user, "accountant", "super_admin") },
    ],
  },
  payments: {
    title: "Payment vouchers",
    description: "Controlled approval and recording of supplier payments against matched invoices.",
    endpoint: "admin/payment-vouchers",
    searchPlaceholder: "Search voucher or supplier",
    roles: ["super_admin", "accountant", "gm", "auditor"],
    statusOptions: ["draft", "submitted", "approved", "returned", "rejected", "paid", "cancelled"],
    columns: [
      { label: "Voucher", key: "voucher_number" },
      { label: "Supplier", key: "supplier.name" },
      { label: "Invoice", key: "supplier_invoice.invoice_number" },
      { label: "Requested", key: "amount_requested", format: "money" },
      { label: "Payment date", key: "payment_date", format: "date" },
      { label: "Status", key: "status", format: "status" },
    ],
    actions: [
      { label: "Submit voucher", tone: "primary", path: (item) => `admin/payment-vouchers/${id(item)}/submit`, visible: (item, user) => status(item) === "draft" && role(user, "accountant", "super_admin") },
      { label: "Approve", tone: "primary", inputLabel: "Approval comments", path: (item) => `admin/payment-vouchers/${id(item)}/decision`, body: (input) => ({ decision: "approve", comments: input || null }), visible: (item, user) => status(item) === "submitted" && role(user, "gm", "super_admin") },
      { label: "Return", inputLabel: "Required corrections", path: (item) => `admin/payment-vouchers/${id(item)}/decision`, body: (input) => ({ decision: "return", reason: input }), visible: (item, user) => status(item) === "submitted" && role(user, "gm", "super_admin") },
      { label: "Reject", tone: "danger", inputLabel: "Reason for rejection", path: (item) => `admin/payment-vouchers/${id(item)}/decision`, body: (input) => ({ decision: "reject", reason: input }), visible: (item, user) => status(item) === "submitted" && role(user, "gm", "super_admin") },
    ],
  },
  closures: {
    title: "Procurement closures",
    description: "Final obligation checks, requester confirmation, and controlled exception closure.",
    endpoint: "admin/procurement-closures",
    searchPlaceholder: "Search requisition or purchase order",
    statusOptions: ["draft", "pending_requester_confirmation", "confirmed", "closed_with_exception", "cancelled"],
    columns: [
      { label: "Requisition", key: "purchase_requisition.requisition_number" },
      { label: "Purchase order", key: "purchase_order.purchase_order_number" },
      { label: "Summary", key: "completion_summary" },
      { label: "Closed at", key: "closed_at", format: "date" },
      { label: "Status", key: "closure_status", format: "status" },
    ],
    actions: [
      { label: "Request confirmation", tone: "primary", path: (item) => `admin/procurement-closures/${id(item)}/submit`, visible: (item, user) => status(item) === "draft" && role(user, "procurement_officer", "super_admin") },
      { label: "Close procurement", tone: "primary", path: (item) => `admin/procurement-closures/${id(item)}/close`, visible: (item, user) => status(item) === "confirmed" && role(user, "procurement_officer", "super_admin") },
    ],
  },
  budgets: {
    title: "Entity budgets",
    description: "Approved allocations, commitments, expenditure, and remaining availability.",
    endpoint: "admin/entity-budgets",
    searchPlaceholder: "Filter budgets",
    roles: ["accountant", "gm", "ceo"],
    statusOptions: ["draft", "submitted", "returned", "approved", "rejected", "closed"],
    columns: [
      { label: "Entity", key: "business_entity.name" },
      { label: "Financial year", key: "financial_year.name" },
      { label: "Approved", key: "approved_amount", format: "money" },
      { label: "Committed", key: "committed_amount", format: "money" },
      { label: "Spent", key: "spent_amount", format: "money" },
      { label: "Available", key: "available_amount", format: "money" },
      { label: "Status", key: "status", format: "status" },
    ],
    actions: [
      { label: "Submit budget", tone: "primary", path: (item) => `admin/entity-budgets/${id(item)}/submit`, visible: (item, user) => ["draft", "returned"].includes(status(item)) && role(user, "accountant") },
    ],
  },
  suppliers: {
    title: "Suppliers",
    description: "Approved supplier records, contacts, tax references, and sourcing availability.",
    endpoint: "admin/suppliers",
    searchPlaceholder: "Search supplier or code",
    create: "supplier",
    columns: [
      { label: "Supplier", key: "name" },
      { label: "Code", key: "code" },
      { label: "Contact", key: "contact_person" },
      { label: "Email", key: "email" },
      { label: "Phone", key: "phone" },
      { label: "Active", key: "is_active", format: "boolean" },
    ],
    actions: [
      { label: "Activate", tone: "primary", method: "PATCH", path: (item) => `admin/suppliers/${id(item)}/activate`, visible: (item, user) => item.is_active === false && role(user, "super_admin", "procurement_officer") },
      { label: "Deactivate", tone: "danger", method: "PATCH", prompt: "Deactivate this supplier?", path: (item) => `admin/suppliers/${id(item)}/deactivate`, visible: (item, user) => item.is_active === true && role(user, "super_admin", "procurement_officer") },
    ],
  },
  entities: {
    title: "Business entities",
    description: "Legal and operating entities that own departments, budgets, and procurement activity.",
    endpoint: "admin/entities",
    searchPlaceholder: "Search entity or code",
    columns: [
      { label: "Entity", key: "name" },
      { label: "Code", key: "code" },
      { label: "Departments", key: "departments_count" },
      { label: "Active", key: "is_active", format: "boolean" },
    ],
  },
  departments: {
    title: "Departments",
    description: "Organisational ownership, reporting lines, and requester access boundaries.",
    endpoint: "admin/departments",
    searchPlaceholder: "Search department or code",
    columns: [
      { label: "Department", key: "name" },
      { label: "Code", key: "code" },
      { label: "Business entity", key: "business_entity.name" },
      { label: "Users", key: "users_count" },
      { label: "Active", key: "is_active", format: "boolean" },
    ],
  },
  users: {
    title: "Users and access",
    description: "Accounts, reporting lines, assigned roles, and active access status.",
    endpoint: "admin/users",
    searchPlaceholder: "Search name or email",
    roles: ["super_admin", "gm"],
    columns: [
      { label: "User", key: "name" },
      { label: "Email", key: "email" },
      { label: "Department", key: "department.name" },
      { label: "Job title", key: "job_title" },
      { label: "Active", key: "is_active", format: "boolean" },
    ],
  },
  reports: {
    title: "Procurement reports",
    description: "Operational registers and audit-ready views across the procurement lifecycle.",
    endpoint: "admin/reports/requisition-register",
    searchPlaceholder: "Filter requisition register",
    roles: ["super_admin", "gm", "accountant", "procurement_officer", "auditor"],
    columns: [
      { label: "Requisition", key: "requisition_number" },
      { label: "Entity", key: "business_entity.name" },
      { label: "Department", key: "department.name" },
      { label: "Requester", key: "requester.name" },
      { label: "Created", key: "created_at", format: "date" },
      { label: "Status", key: "status", format: "status" },
    ],
  },
};
