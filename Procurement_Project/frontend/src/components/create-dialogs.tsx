"use client";

import { useAuth } from "@/components/auth-provider";
import { api, ApiError, collectionFrom } from "@/lib/api";
import type { JsonRecord } from "@/lib/types";
import { LoaderCircle, Plus, Trash2, X } from "lucide-react";
import { FormEvent, useEffect, useMemo, useState } from "react";

type DialogProps = { open: boolean; close: () => void; completed: (message: string) => void };

export function SupplierDialog({ open, close, completed }: DialogProps) {
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState("");
  const [categories, setCategories] = useState<JsonRecord[]>([]);

  useEffect(() => {
    if (open) void api<{ data: JsonRecord[] }>("portal/supplier-categories").then((response) => setCategories(response.data));
  }, [open]);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSubmitting(true);
    setError("");
    const form = new FormData(event.currentTarget);
    const values = Object.fromEntries(form.entries());
    try {
      await api("admin/suppliers", {
        method: "POST",
        body: JSON.stringify({ ...values, vat_registered: form.has("vat_registered"), regulated_supplier: form.has("regulated_supplier"), category_ids: form.getAll("category_ids").map(Number) }),
      });
      completed("Supplier created successfully.");
      close();
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : "Unable to create supplier.");
    } finally {
      setSubmitting(false);
    }
  }

  if (!open) return null;
  return (
    <Modal title="Add supplier" subtitle="Create a supplier in the same compliance workflow used by portal registration." close={close} large>
      <form onSubmit={submit} className="dialog-form">
        {error && <div className="form-alert">{error}</div>}
        <div className="form-grid two"><Field label="Legal name" name="legal_name" required /><Field label="Trading name" name="trading_name" /><Field label="Supplier code" name="code" required /><label className="field"><span>Supplier type</span><select name="supplier_type" required defaultValue="limited_company">{["limited_company", "business_name", "partnership", "sole_proprietor", "ngo", "government_entity", "other"].map((type) => <option key={type} value={type}>{type.replaceAll("_", " ")}</option>)}</select></label><Field label="Registration number" name="registration_number" /><Field label="BRELA number" name="brela_registration_number" /><Field label="TIN number" name="tin_number" required /><Field label="VAT registration number" name="vat_registration_number" /><label className="checkbox-field"><input type="checkbox" name="vat_registered" /><span>VAT registered</span></label><Field label="Business licence number" name="business_license_number" /><Field label="Licence expiry" name="business_license_expiry_date" type="date" /><Field label="Tax clearance number" name="tax_clearance_number" /><Field label="Tax clearance expiry" name="tax_clearance_expiry_date" type="date" /><Field label="Primary contact" name="primary_contact_name" required /><Field label="Position" name="primary_contact_position" /><Field label="Email" name="primary_contact_email" type="email" /><Field label="Phone" name="primary_contact_phone" required /><Field label="Physical office address" name="physical_office_address" required wide /><Field label="District" name="district" /><Field label="Region" name="region" required /><Field label="Country" name="country" defaultValue="Tanzania" /><Field label="Products and services" name="products_services" required wide /><Field label="Delivery coverage" name="delivery_coverage_areas" wide /><fieldset className="category-picker wide"><legend>Approved categories</legend>{categories.map((category) => <label key={String(category.id)}><input type="checkbox" name="category_ids" value={String(category.id)} /><span>{String(category.name)}</span></label>)}</fieldset><label className="checkbox-field wide"><input type="checkbox" name="regulated_supplier" /><span>Regulated goods or services supplier</span></label></div>
        <div className="dialog-actions"><button type="button" className="secondary-button" onClick={close}>Cancel</button><button className="primary-button" disabled={submitting}>{submitting && <LoaderCircle className="spin" size={16} />}Create supplier</button></div>
      </form>
    </Modal>
  );
}

type RequisitionItem = { item_name: string; specification: string; quantity: string; unit: string; estimated_unit_price: string; notes: string };
const emptyItem = (): RequisitionItem => ({ item_name: "", specification: "", quantity: "1", unit: "", estimated_unit_price: "", notes: "" });

type RequisitionDialogProps = DialogProps & { requisition?: JsonRecord | null };

export function RequisitionDialog({ open, close, completed, requisition }: RequisitionDialogProps) {
  const { user } = useAuth();
  const [items, setItems] = useState<RequisitionItem[]>([emptyItem()]);
  const [requiredDate, setRequiredDate] = useState("");
  const [purpose, setPurpose] = useState("");
  const [estimateNote, setEstimateNote] = useState("");
  const [categories, setCategories] = useState<JsonRecord[]>([]);
  const [categoryId, setCategoryId] = useState("");
  const [budgetCheck, setBudgetCheck] = useState<JsonRecord | null>(null);
  const [budgetChecking, setBudgetChecking] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState("");
  const total = useMemo(() => items.reduce((sum, item) => sum + Number(item.quantity || 0) * Number(item.estimated_unit_price || 0), 0), [items]);
  const hasFullBudget = budgetCheck !== null && Object.prototype.hasOwnProperty.call(budgetCheck, "total_allocated_budget");

  useEffect(() => {
    if (!open) return;

    const existingItems = (requisition?.items as JsonRecord[] | undefined) ?? [];
    setItems(existingItems.length > 0 ? existingItems.map((item) => ({
      item_name: String(item.item_name ?? ""),
      specification: String(item.specification ?? ""),
      quantity: String(item.quantity ?? "1"),
      unit: String(item.unit ?? ""),
      estimated_unit_price: String(item.estimated_unit_price ?? ""),
      notes: String(item.notes ?? ""),
    })) : [emptyItem()]);
    setRequiredDate(String(requisition?.required_date ?? ""));
    setPurpose(String(requisition?.purpose ?? ""));
    setEstimateNote(String(requisition?.estimate_difference_reason ?? ""));
    setCategoryId(String((requisition?.supplier_category as JsonRecord | undefined)?.id ?? ""));
    void api<{ data: JsonRecord[] }>("portal/supplier-categories").then((response) => setCategories(response.data));
    setError("");
  }, [open, requisition]);

  useEffect(() => {
    const entityId = Number((requisition?.business_entity as JsonRecord | undefined)?.id ?? user?.department?.business_entity?.id ?? 0);
    if (!open || !entityId) return;

    const timer = window.setTimeout(() => {
      setBudgetChecking(true);
      api<{ data: JsonRecord }>(`admin/requisition-budget-check?business_entity_id=${entityId}&amount=${Math.max(0, total)}`)
        .then((response) => setBudgetCheck(response.data))
        .catch(() => setBudgetCheck(null))
        .finally(() => setBudgetChecking(false));
    }, 250);

    return () => window.clearTimeout(timer);
  }, [open, requisition, total, user?.department?.business_entity?.id]);

  function updateItem(index: number, key: keyof RequisitionItem, value: string) {
    setItems((current) => current.map((item, itemIndex) => itemIndex === index ? { ...item, [key]: value } : item));
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!user?.department?.business_entity) {
      setError("Your account must be assigned to a department and business entity before creating a requisition.");
      return;
    }
    setSubmitting(true);
    setError("");
    const requisitionEntity = requisition?.business_entity as JsonRecord | undefined;
    const requisitionDepartment = requisition?.department as JsonRecord | undefined;
    const payload = {
      business_entity_id: Number(requisitionEntity?.id ?? user.department.business_entity.id),
      department_id: Number(requisitionDepartment?.id ?? user.department.id),
      supplier_category_id: Number(categoryId),
      required_date: requiredDate,
      purpose,
      estimated_amount: total,
      items: items.map((item) => ({ ...item, quantity: Number(item.quantity), estimated_unit_price: Number(item.estimated_unit_price), estimated_total: Number(item.quantity) * Number(item.estimated_unit_price) })),
      estimate_difference_reason: estimateNote || null,
    };
    try {
      await api(requisition ? `admin/purchase-requisitions/${Number(requisition.id)}` : "admin/purchase-requisitions", { method: requisition ? "PATCH" : "POST", body: JSON.stringify(payload) });
      completed(requisition ? "Requisition draft updated successfully." : "Requisition draft created successfully.");
      close();
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : `Unable to ${requisition ? "update" : "create"} requisition.`);
    } finally {
      setSubmitting(false);
    }
  }

  if (!open) return null;
  return (
    <Modal title={requisition ? "Edit purchase requisition" : "New purchase requisition"} subtitle={`${user?.department?.name ?? "Department"} · ${user?.department?.business_entity?.name ?? "Business entity"}`} close={close} large>
      <form onSubmit={submit} className="dialog-form">
        {error && <div className="form-alert">{error}</div>}
        <div className="form-grid two"><label className="field"><span>Required date</span><input name="required_date" type="date" min={new Date().toISOString().slice(0, 10)} value={requiredDate} onChange={(event) => setRequiredDate(event.target.value)} required /></label><label className="field"><span>Procurement category</span><select value={categoryId} onChange={(event) => setCategoryId(event.target.value)} required><option value="" disabled>Select category</option>{categories.map((category) => <option key={String(category.id)} value={String(category.id)}>{String(category.name)}</option>)}</select></label><label className="field wide"><span>Business purpose</span><textarea name="purpose" rows={3} value={purpose} onChange={(event) => setPurpose(event.target.value)} required placeholder="Describe why this purchase is required" /></label></div>
        <div className="line-items-heading"><div><h3>Requested items</h3><p>Quantities and estimates establish the initial budget commitment.</p></div><button type="button" className="secondary-button compact" onClick={() => setItems((current) => [...current, emptyItem()])}><Plus size={15} />Add line</button></div>
        <div className="line-items">
          {items.map((item, index) => <div className="line-item" key={index}><div className="line-number">{index + 1}</div><div className="form-grid item-grid requisition-item-grid"><label className="field"><span>Product</span><input value={item.item_name} onChange={(event) => updateItem(index, "item_name", event.target.value)} required /></label><label className="field"><span>Product description</span><input value={item.specification} onChange={(event) => updateItem(index, "specification", event.target.value)} /></label><label className="field"><span>Quantity</span><input type="number" min="0.01" step="0.01" inputMode="decimal" value={item.quantity} onChange={(event) => updateItem(index, "quantity", event.target.value)} required /></label><label className="field"><span>Unit of measure</span><input value={item.unit} onChange={(event) => updateItem(index, "unit", event.target.value)} required placeholder="Carton, each, kg" pattern=".*[^0-9.,\s].*" title="Enter a unit such as carton, each, box, or kg" /></label><label className="field"><span>Unit price (TZS)</span><input type="number" min="0" step="0.01" inputMode="decimal" value={item.estimated_unit_price} onChange={(event) => updateItem(index, "estimated_unit_price", event.target.value)} required placeholder="0.00" /></label><label className="field"><span>Product note <small>Optional</small></span><input value={item.notes} onChange={(event) => updateItem(index, "notes", event.target.value)} /></label><div className="line-total"><span>Line total</span><strong>TZS {(Number(item.quantity || 0) * Number(item.estimated_unit_price || 0)).toLocaleString()}</strong></div></div>{items.length > 1 && <button type="button" className="icon-button danger" onClick={() => setItems((current) => current.filter((_, itemIndex) => itemIndex !== index))} title="Remove line"><Trash2 size={17} /></button>}</div>)}
        </div>
        <label className="field"><span>Estimate note <small>Optional</small></span><textarea name="estimate_difference_reason" rows={2} value={estimateNote} onChange={(event) => setEstimateNote(event.target.value)} placeholder="Add context about the estimate where useful" /></label>
        <section className={`draft-budget-check ${budgetCheck?.sufficient ? "sufficient" : "shortfall"}`}>
          <div className="budget-check-outcome"><span>Organisation budget check</span><strong>{budgetChecking ? "Checking…" : budgetCheck?.sufficient ? "Budget check available" : "Funding review required"}</strong></div>
          {hasFullBudget && <><div><span>Total allocated budget</span><strong>{budgetCheck?.total_allocated_budget == null ? "Not configured" : `TZS ${Number(budgetCheck.total_allocated_budget).toLocaleString(undefined, { minimumFractionDigits: 2 })}`}</strong></div><div><span>Total used so far</span><strong>{budgetCheck?.total_used_amount == null ? "—" : `TZS ${Number(budgetCheck.total_used_amount).toLocaleString(undefined, { minimumFractionDigits: 2 })}`}</strong></div><div><span>Available amount</span><strong>{budgetCheck?.available_amount == null ? "—" : `TZS ${Number(budgetCheck.available_amount).toLocaleString(undefined, { minimumFractionDigits: 2 })}`}</strong></div></>}
          <p>{String(budgetCheck?.message ?? "The budget check is advisory and does not prevent saving this draft.")}</p>
        </section>
        <div className="requisition-total"><span>Estimated requisition total</span><strong>TZS {total.toLocaleString(undefined, { minimumFractionDigits: 2 })}</strong></div>
        <div className="dialog-actions"><button type="button" className="secondary-button" onClick={close}>Cancel</button><button className="primary-button" disabled={submitting || total <= 0 || !categoryId}>{submitting && <LoaderCircle className="spin" size={16} />}{requisition ? "Update draft" : "Save draft"}</button></div>
      </form>
    </Modal>
  );
}

type SupplierOffer = { key: number; supplier_id: string; valid_until: string; notes: string; prices: Record<number, string> };
const emptySupplierOffer = (key: number): SupplierOffer => ({ key, supplier_id: "", valid_until: "", notes: "", prices: {} });

export function ProformaDialog({ open, close, completed }: DialogProps) {
  const { user } = useAuth();
  const [requisitions, setRequisitions] = useState<JsonRecord[]>([]);
  const [suppliers, setSuppliers] = useState<JsonRecord[]>([]);
  const [requisitionId, setRequisitionId] = useState("");
  const [route, setRoute] = useState<"registered" | "other_suppliers">("registered");
  const [offers, setOffers] = useState<SupplierOffer[]>([emptySupplierOffer(1)]);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState("");
  const selectedRequisition = useMemo(() => requisitions.find((item) => String(item.id) === requisitionId), [requisitionId, requisitions]);
  const requestItems = useMemo(() => (selectedRequisition?.items as JsonRecord[] | undefined) ?? [], [selectedRequisition]);
  const eligibleSuppliers = useMemo(() => {
    const categoryId = Number((selectedRequisition?.supplier_category as JsonRecord | undefined)?.id ?? 0);
    if (!categoryId) return [];
    return suppliers.filter((supplier) => ((supplier.categories as JsonRecord[] | undefined) ?? []).some((category) => Number(category.id) === categoryId));
  }, [selectedRequisition, suppliers]);
  const isPublicRequest = route === "other_suppliers";

  useEffect(() => {
    if (!open) return;
    Promise.all([
      api<JsonRecord>("admin/purchase-requisitions?status=approved_for_sourcing&per_page=100"),
      api<JsonRecord>("admin/suppliers?status=active&per_page=100"),
    ]).then(([requisitionData, supplierData]) => {
      setRequisitions(collectionFrom(requisitionData).rows);
      setSuppliers(collectionFrom(supplierData).rows.filter((supplier) => supplier.is_active !== false));
    }).catch((caught) => setError(caught instanceof ApiError ? caught.message : "Unable to load proforma options."));
  }, [open]);

  function chooseRequisition(value: string) {
    setRequisitionId(value);
    setOffers([emptySupplierOffer(1)]);
  }

  function updateOffer(key: number, values: Partial<SupplierOffer>) {
    setOffers((current) => current.map((offer) => offer.key === key ? { ...offer, ...values } : offer));
  }

  function updatePrice(key: number, itemId: number, value: string) {
    setOffers((current) => current.map((offer) => offer.key === key ? { ...offer, prices: { ...offer.prices, [itemId]: value } } : offer));
  }

  function offerTotal(offer: SupplierOffer) {
    return requestItems.reduce((sum, item) => sum + Number(item.quantity ?? 0) * Number(offer.prices[Number(item.id)] ?? 0), 0);
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSubmitting(true);
    setError("");
    const form = new FormData(event.currentTarget);
    try {
      if (isPublicRequest) {
        await api(`admin/purchase-requisitions/${Number(requisitionId)}/other-suppliers-tender`, {
          method: "POST",
          body: JSON.stringify({
            title: form.get("title"),
            public_summary: form.get("public_summary"),
            submission_deadline: form.get("submission_deadline"),
            expected_delivery_date: form.get("expected_delivery_date") || null,
            delivery_location: form.get("delivery_location") || null,
            contact_email: form.get("contact_email") || null,
            contact_phone: form.get("contact_phone") || null,
            eligibility_requirements: form.get("eligibility_requirements") || null,
            submission_instructions: form.get("submission_instructions") || null,
            terms_and_conditions: form.get("terms_and_conditions") || null,
          }),
        });
        completed("Public RFQ sent to the GM for approval. It will appear on the supplier portal only after publication approval.");
      } else {
        await api("admin/supplier-quotations/batch", {
          method: "POST",
          body: JSON.stringify({
            purchase_requisition_id: Number(requisitionId),
            offers: offers.map((offer) => ({
              supplier_id: Number(offer.supplier_id),
              valid_until: offer.valid_until || null,
              notes: offer.notes || null,
              prices: requestItems.map((item) => ({ purchase_requisition_item_id: Number(item.id), unit_price: Number(offer.prices[Number(item.id)]) })),
            })),
          }),
        });
        completed(`${offers.length} supplier proforma${offers.length === 1 ? "" : "s"} created from the requisition items.`);
      }
      setRequisitionId("");
      setRoute("registered");
      setOffers([emptySupplierOffer(1)]);
      close();
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : "Unable to create proforma.");
    } finally {
      setSubmitting(false);
    }
  }

  if (!open) return null;
  return (
    <Modal title="New supplier proforma" subtitle="Use a registered supplier or request quotations from other suppliers through the public portal." close={close} large>
      <form onSubmit={submit} className="dialog-form">
        {error && <div className="form-alert">{error}</div>}
        <div className="form-grid two">
          <label className="field"><span>Approved requisition</span><select name="purchase_requisition_id" required value={requisitionId} onChange={(event) => chooseRequisition(event.target.value)}><option value="" disabled>Select requisition</option>{requisitions.map((item) => <option key={String(item.id)} value={String(item.id)}>{String(item.requisition_number)} - {String(item.purpose)}</option>)}</select></label>
          <label className="field"><span>Sourcing route</span><select value={route} onChange={(event) => setRoute(event.target.value as "registered" | "other_suppliers")}><option value="registered">Price registered suppliers</option><option value="other_suppliers">Other suppliers — publish an RFQ</option></select></label>
        </div>
        {selectedRequisition && <section className="proforma-request-preview"><header><div><span className="eyebrow">Requisition items</span><h3>{String(selectedRequisition.requisition_number)} — {String(selectedRequisition.purpose)}</h3></div><span>{requestItems.length} requested item{requestItems.length === 1 ? "" : "s"}</span></header><div className="table-wrap"><table><thead><tr><th>#</th><th>Product</th><th>Description / specification</th><th>Quantity</th><th>Unit</th></tr></thead><tbody>{requestItems.map((item, index) => <tr key={String(item.id)}><td>{index + 1}</td><td><strong>{String(item.item_name ?? "Item")}</strong></td><td>{String(item.specification ?? "No description provided")}</td><td>{Number(item.quantity ?? 0).toLocaleString()}</td><td>{String(item.unit ?? "-")}</td></tr>)}</tbody></table></div><p>Internal estimates and budget prices are protected. Only the requested descriptions, quantities, and units are shown.</p></section>}
        {isPublicRequest ? <>
          <div className="form-alert info">The requisition items will be copied without internal prices or budget details. The GM must approve publication before suppliers can see this RFQ.</div>
          <div className="form-grid two"><Field label="Public RFQ title" name="title" required /><label className="field wide"><span>Public summary</span><textarea name="public_summary" rows={3} required placeholder="Describe the requirement without internal budget or approval details." /></label><Field label="Bid submission deadline" name="submission_deadline" type="datetime-local" required /><Field label="Expected delivery date" name="expected_delivery_date" type="date" /><Field label="Delivery location" name="delivery_location" /><Field label="Procurement contact email" name="contact_email" type="email" defaultValue={user?.email ?? ""} required /><Field label="Procurement contact phone" name="contact_phone" /><label className="field wide"><span>Eligibility requirements</span><textarea name="eligibility_requirements" rows={2} /></label><label className="field wide"><span>Submission instructions</span><textarea name="submission_instructions" rows={2} /></label><label className="field wide"><span>Terms and conditions</span><textarea name="terms_and_conditions" rows={2} /></label></div>
        </> : <>
          <div className="line-items-heading"><div><h3>Supplier pricing</h3><p>Add the suppliers who quoted for this requisition and enter only their offered prices.</p></div><button type="button" className="secondary-button compact" disabled={!selectedRequisition} onClick={() => setOffers((current) => [...current, emptySupplierOffer(Math.max(...current.map((offer) => offer.key), 0) + 1)])}><Plus size={15} />Add supplier</button></div>
          <div className="supplier-offer-list">{offers.map((offer, offerIndex) => <section className="supplier-offer-card" key={offer.key}><header><div className="supplier-offer-number">{offerIndex + 1}</div><div><span>Supplier proforma</span><strong>{offer.supplier_id ? String(suppliers.find((supplier) => String(supplier.id) === offer.supplier_id)?.name ?? "Supplier") : `Supplier ${offerIndex + 1}`}</strong></div><div className="supplier-offer-code"><span>Proforma number</span><strong>Auto-generated</strong></div>{offers.length > 1 && <button type="button" className="icon-button danger" title="Remove supplier" onClick={() => setOffers((current) => current.filter((item) => item.key !== offer.key))}><Trash2 size={16} /></button>}</header><div className="form-grid two"><label className="field"><span>Registered supplier</span><select value={offer.supplier_id} onChange={(event) => updateOffer(offer.key, { supplier_id: event.target.value })} required><option value="" disabled>{eligibleSuppliers.length === 0 ? "No category-eligible suppliers" : "Select supplier"}</option>{eligibleSuppliers.map((supplier) => <option key={String(supplier.id)} value={String(supplier.id)} disabled={offers.some((other) => other.key !== offer.key && other.supplier_id === String(supplier.id))}>{String(supplier.name)} ({String(supplier.code)})</option>)}</select></label><label className="field"><span>Valid until</span><input type="date" value={offer.valid_until} onChange={(event) => updateOffer(offer.key, { valid_until: event.target.value })} /></label></div><div className="supplier-price-table"><table><thead><tr><th>Requested product</th><th>Quantity</th><th>Offered unit price (TZS)</th><th>Offered subtotal</th></tr></thead><tbody>{requestItems.map((item) => { const itemId = Number(item.id); const unitPrice = Number(offer.prices[itemId] ?? 0); return <tr key={itemId}><td><strong>{String(item.item_name ?? "Item")}</strong><small>{String(item.specification ?? "No description")}</small></td><td>{Number(item.quantity ?? 0).toLocaleString()} {String(item.unit ?? "")}</td><td><input aria-label={`${String(item.item_name)} unit price for supplier ${offerIndex + 1}`} type="number" min="0" step="0.01" inputMode="decimal" value={offer.prices[itemId] ?? ""} onChange={(event) => updatePrice(offer.key, itemId, event.target.value)} required /></td><td><strong>TZS {(Number(item.quantity ?? 0) * unitPrice).toLocaleString(undefined, { minimumFractionDigits: 2 })}</strong></td></tr>; })}</tbody></table></div><div className="supplier-offer-footer"><label className="field"><span>Supplier notes</span><textarea rows={2} value={offer.notes} onChange={(event) => updateOffer(offer.key, { notes: event.target.value })} /></label><div className="requisition-total"><span>Supplier total</span><strong>TZS {offerTotal(offer).toLocaleString(undefined, { minimumFractionDigits: 2 })}</strong></div></div></section>)}</div>
        </>}
        <div className="dialog-actions"><button type="button" className="secondary-button" onClick={close}>Cancel</button><button className="primary-button" disabled={submitting || !requisitionId || requestItems.length === 0 || (!isPublicRequest && (offers.some((offer) => !offer.supplier_id || requestItems.some((item) => offer.prices[Number(item.id)] === undefined || offer.prices[Number(item.id)] === ""))))}>{submitting && <LoaderCircle className="spin" size={16} />}{isPublicRequest ? "Send to GM for publication" : `Save ${offers.length} proforma${offers.length === 1 ? "" : "s"}`}</button></div>
      </form>
    </Modal>
  );
}

type PurchaseOrderLine = {
  id: number;
  item_name: string;
  quantity_ordered: number;
  quantity_received: number;
  quantity_invoiced: number;
  unit: string;
  unit_price: number;
};

function purchaseOrderLines(order?: JsonRecord): PurchaseOrderLine[] {
  return ((order?.items as JsonRecord[] | undefined) ?? []).map((item) => ({
    id: Number(item.id),
    item_name: String(item.item_name ?? "Item"),
    quantity_ordered: Number(item.quantity_ordered ?? 0),
    quantity_received: Number(item.quantity_received ?? 0),
    quantity_invoiced: Number(item.quantity_invoiced ?? 0),
    unit: String(item.unit ?? ""),
    unit_price: Number(item.unit_price ?? 0),
  }));
}

export function ReceiptDialog({ open, close, completed }: DialogProps) {
  const [orders, setOrders] = useState<JsonRecord[]>([]);
  const [orderId, setOrderId] = useState("");
  const [quantities, setQuantities] = useState<Record<number, string>>({});
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState("");
  const order = orders.find((item) => String(item.id) === orderId);
  const lines = purchaseOrderLines(order).filter((line) => line.quantity_ordered > line.quantity_received);

  useEffect(() => {
    if (!open) return;
    setError("");
    api<JsonRecord>("admin/purchase-orders?per_page=100")
      .then((data) => setOrders(collectionFrom(data).rows.filter((item) => ["issued", "acknowledged", "partially_received"].includes(String(item.status)))))
      .catch((caught) => setError(caught instanceof ApiError ? caught.message : "Unable to load issued LPOs."));
  }, [open]);

  useEffect(() => {
    setQuantities(Object.fromEntries(lines.map((line) => [line.id, String(line.quantity_ordered - line.quantity_received)])));
  // The selected LPO is the intentional reset boundary for receipt quantities.
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [orderId]);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSubmitting(true);
    setError("");
    const form = new FormData(event.currentTarget);
    const items = lines
      .map((line) => ({ purchase_order_item_id: line.id, quantity_received: Number(quantities[line.id] ?? 0) }))
      .filter((line) => line.quantity_received > 0);
    if (items.length === 0) {
      setError("Enter at least one delivered quantity.");
      setSubmitting(false);
      return;
    }
    try {
      await api("admin/goods-receipt-notes", {
        method: "POST",
        body: JSON.stringify({
          purchase_order_id: Number(orderId),
          received_date: form.get("received_date"),
          delivery_note_number: form.get("delivery_note_number"),
          delivery_condition: form.get("delivery_condition"),
          inspection_required: true,
          received_location: form.get("received_location") || null,
          notes: form.get("notes") || null,
          items,
        }),
      });
      completed("Delivery note recorded. Submit it for independent store verification and signature.");
      setOrderId("");
      close();
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : "Unable to record the delivery.");
    } finally {
      setSubmitting(false);
    }
  }

  if (!open) return null;
  return (
    <Modal title="Record product delivery" subtitle="Associate the supplier delivery note and received quantities with an issued LPO." close={close} large>
      <form onSubmit={submit} className="dialog-form">
        {error && <div className="form-alert">{error}</div>}
        <div className="form-grid two">
          <label className="field"><span>Issued LPO</span><select value={orderId} onChange={(event) => setOrderId(event.target.value)} required><option value="" disabled>Select LPO</option>{orders.map((item) => <option key={String(item.id)} value={String(item.id)}>{String(item.purchase_order_number)} - {String((item.supplier as JsonRecord | undefined)?.name ?? "Supplier")}</option>)}</select></label>
          <Field label="Delivery note number" name="delivery_note_number" required />
          <label className="field"><span>Received date</span><input name="received_date" type="date" max={new Date().toISOString().slice(0, 10)} defaultValue={new Date().toISOString().slice(0, 10)} required /></label>
          <Field label="Received location" name="received_location" required />
          <label className="field"><span>Delivery condition</span><select name="delivery_condition" defaultValue="good"><option value="good">Good</option><option value="partial">Partial</option><option value="damaged">Damaged</option><option value="rejected">Rejected at delivery</option></select></label>
        </div>
        {order && <><div className="line-items-heading"><div><h3>Delivered items</h3><p>Record only the quantities shown on this delivery note.</p></div></div><div className="line-items">{lines.map((line, index) => <div className="line-item" key={line.id}><div className="line-number">{index + 1}</div><div className="form-grid item-grid"><div className="field"><span>Item</span><strong>{line.item_name}</strong></div><div className="field"><span>Outstanding</span><strong>{line.quantity_ordered - line.quantity_received} {line.unit}</strong></div><label className="field"><span>Delivered quantity</span><input type="number" min="0" max={line.quantity_ordered - line.quantity_received} step="0.01" value={quantities[line.id] ?? ""} onChange={(event) => setQuantities((current) => ({ ...current, [line.id]: event.target.value }))} /></label></div></div>)}</div></>}
        <label className="field"><span>Delivery notes</span><textarea name="notes" rows={2} /></label>
        <div className="dialog-actions"><button type="button" className="secondary-button" onClick={close}>Cancel</button><button className="primary-button" disabled={submitting || !order || lines.length === 0}>{submitting && <LoaderCircle className="spin" size={16} />}Save delivery note</button></div>
      </form>
    </Modal>
  );
}

export function InvoiceDialog({ open, close, completed }: DialogProps) {
  const [orders, setOrders] = useState<JsonRecord[]>([]);
  const [orderId, setOrderId] = useState("");
  const [quantities, setQuantities] = useState<Record<number, string>>({});
  const [prices, setPrices] = useState<Record<number, string>>({});
  const [discount, setDiscount] = useState("0");
  const [tax, setTax] = useState("0");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState("");
  const order = orders.find((item) => String(item.id) === orderId);
  const lines = purchaseOrderLines(order).filter((line) => line.quantity_received > line.quantity_invoiced);
  const subtotal = lines.reduce((sum, line) => sum + Number(quantities[line.id] ?? 0) * Number(prices[line.id] ?? 0), 0);
  const total = Math.max(0, subtotal - Number(discount || 0) + Number(tax || 0));

  useEffect(() => {
    if (!open) return;
    setError("");
    api<JsonRecord>("admin/purchase-orders?per_page=100")
      .then((data) => setOrders(collectionFrom(data).rows.filter((item) => ["partially_received", "fully_received"].includes(String(item.status)))))
      .catch((caught) => setError(caught instanceof ApiError ? caught.message : "Unable to load received LPOs."));
  }, [open]);

  useEffect(() => {
    setQuantities(Object.fromEntries(lines.map((line) => [line.id, String(line.quantity_received - line.quantity_invoiced)])));
    setPrices(Object.fromEntries(lines.map((line) => [line.id, String(line.unit_price)])));
  // The selected LPO is the intentional reset boundary for invoice lines.
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [orderId]);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSubmitting(true);
    setError("");
    const form = new FormData(event.currentTarget);
    const items = lines
      .map((line) => ({ purchase_order_item_id: line.id, quantity_invoiced: Number(quantities[line.id] ?? 0), unit_price: Number(prices[line.id] ?? 0) }))
      .filter((line) => line.quantity_invoiced > 0 && line.unit_price > 0);
    if (items.length === 0) {
      setError("Enter at least one invoiced quantity and unit price.");
      setSubmitting(false);
      return;
    }
    try {
      await api("admin/supplier-invoices", {
        method: "POST",
        body: JSON.stringify({
          invoice_number: form.get("invoice_number"),
          purchase_order_id: Number(orderId),
          invoice_date: form.get("invoice_date"),
          due_date: form.get("due_date") || null,
          received_date: form.get("received_date"),
          currency: "TZS",
          subtotal,
          discount_amount: Number(discount || 0),
          tax_amount: Number(tax || 0),
          total_amount: total,
          notes: form.get("notes") || null,
          items,
        }),
      });
      completed("Supplier invoice created and linked to its requisition, proforma, and LPO.");
      setOrderId("");
      close();
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : "Unable to create the invoice.");
    } finally {
      setSubmitting(false);
    }
  }

  if (!open) return null;
  return (
    <Modal title="New supplier invoice" subtitle="Record the supplier invoice only after the warehouse has accepted the delivered product." close={close} large>
      <form onSubmit={submit} className="dialog-form">
        {error && <div className="form-alert">{error}</div>}
        <div className="form-grid two">
          <label className="field"><span>Received LPO</span><select value={orderId} onChange={(event) => setOrderId(event.target.value)} required><option value="" disabled>Select LPO</option>{orders.map((item) => <option key={String(item.id)} value={String(item.id)}>{String(item.purchase_order_number)} - {String((item.requisition as JsonRecord | undefined)?.requisition_number ?? "Requisition")}</option>)}</select></label>
          <Field label="Supplier invoice number" name="invoice_number" required />
          <label className="field"><span>Invoice date</span><input name="invoice_date" type="date" defaultValue={new Date().toISOString().slice(0, 10)} required /></label>
          <label className="field"><span>Received by finance</span><input name="received_date" type="date" defaultValue={new Date().toISOString().slice(0, 10)} required /></label>
          <Field label="Due date" name="due_date" type="date" />
        </div>
        {order && <><div className="line-items-heading"><div><h3>Invoice lines</h3><p>Quantities cannot exceed accepted stock that has not already been invoiced.</p></div></div><div className="line-items">{lines.map((line, index) => <div className="line-item" key={line.id}><div className="line-number">{index + 1}</div><div className="form-grid item-grid"><div className="field"><span>Item</span><strong>{line.item_name}</strong></div><label className="field"><span>Quantity ({line.unit})</span><input type="number" min="0" max={line.quantity_received - line.quantity_invoiced} step="0.01" value={quantities[line.id] ?? ""} onChange={(event) => setQuantities((current) => ({ ...current, [line.id]: event.target.value }))} /></label><label className="field"><span>Unit price</span><input type="number" min="0.01" step="0.01" value={prices[line.id] ?? ""} onChange={(event) => setPrices((current) => ({ ...current, [line.id]: event.target.value }))} /></label><div className="line-total"><span>Line total</span><strong>TZS {(Number(quantities[line.id] ?? 0) * Number(prices[line.id] ?? 0)).toLocaleString()}</strong></div></div></div>)}</div></>}
        <div className="form-grid two"><label className="field"><span>Discount</span><input type="number" min="0" step="0.01" value={discount} onChange={(event) => setDiscount(event.target.value)} /></label><label className="field"><span>Tax</span><input type="number" min="0" step="0.01" value={tax} onChange={(event) => setTax(event.target.value)} /></label></div>
        <label className="field"><span>Finance notes</span><textarea name="notes" rows={2} /></label>
        <div className="requisition-total"><span>Invoice total</span><strong>TZS {total.toLocaleString(undefined, { minimumFractionDigits: 2 })}</strong></div>
        <div className="dialog-actions"><button type="button" className="secondary-button" onClick={close}>Cancel</button><button className="primary-button" disabled={submitting || !order || total <= 0}>{submitting && <LoaderCircle className="spin" size={16} />}Save invoice</button></div>
      </form>
    </Modal>
  );
}

function Modal({ title, subtitle, close, large = false, children }: { title: string; subtitle: string; close: () => void; large?: boolean; children: React.ReactNode }) {
  return <div className="modal-backdrop" role="presentation"><section className={large ? "modal large" : "modal"} role="dialog" aria-modal="true" aria-labelledby="modal-title"><header className="modal-header"><div><h2 id="modal-title">{title}</h2><p>{subtitle}</p></div><button className="icon-button" onClick={close} title="Close"><X size={19} /></button></header><div className="modal-body">{children}</div></section></div>;
}

function Field({ label, name, type = "text", required = false, wide = false, defaultValue }: { label: string; name: string; type?: string; required?: boolean; wide?: boolean; defaultValue?: string }) {
  return <label className={wide ? "field wide" : "field"}><span>{label}</span><input name={name} type={type} required={required} defaultValue={defaultValue} /></label>;
}
