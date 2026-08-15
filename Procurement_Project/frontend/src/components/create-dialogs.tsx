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

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSubmitting(true);
    setError("");
    const form = new FormData(event.currentTarget);
    try {
      await api("admin/suppliers", {
        method: "POST",
        body: JSON.stringify(Object.fromEntries(form.entries())),
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
    <Modal title="Add supplier" subtitle="Create an approved supplier record for sourcing." close={close}>
      <form onSubmit={submit} className="dialog-form">
        {error && <div className="form-alert">{error}</div>}
        <div className="form-grid two"><Field label="Supplier name" name="name" required /><Field label="Supplier code" name="code" required /><Field label="Contact person" name="contact_person" /><Field label="Email" name="email" type="email" /><Field label="Phone" name="phone" /><Field label="Tax number" name="tax_number" /><Field label="Registration number" name="registration_number" /><Field label="Address" name="address" wide /></div>
        <div className="dialog-actions"><button type="button" className="secondary-button" onClick={close}>Cancel</button><button className="primary-button" disabled={submitting}>{submitting && <LoaderCircle className="spin" size={16} />}Create supplier</button></div>
      </form>
    </Modal>
  );
}

type RequisitionItem = { item_name: string; specification: string; quantity: string; unit: string; estimated_unit_price: string; notes: string };
const emptyItem = (): RequisitionItem => ({ item_name: "", specification: "", quantity: "1", unit: "", estimated_unit_price: "", notes: "" });

export function RequisitionDialog({ open, close, completed }: DialogProps) {
  const { user } = useAuth();
  const [items, setItems] = useState<RequisitionItem[]>([emptyItem()]);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState("");
  const total = useMemo(() => items.reduce((sum, item) => sum + Number(item.quantity || 0) * Number(item.estimated_unit_price || 0), 0), [items]);

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
    const form = new FormData(event.currentTarget);
    const payload = {
      business_entity_id: user.department.business_entity.id,
      department_id: user.department.id,
      required_date: form.get("required_date"),
      purpose: form.get("purpose"),
      estimated_amount: total,
      items: items.map((item) => ({ ...item, quantity: Number(item.quantity), estimated_unit_price: Number(item.estimated_unit_price), estimated_total: Number(item.quantity) * Number(item.estimated_unit_price) })),
      estimate_difference_reason: form.get("estimate_difference_reason") || null,
    };
    try {
      await api("admin/purchase-requisitions", { method: "POST", body: JSON.stringify(payload) });
      completed("Requisition draft created successfully.");
      close();
      setItems([emptyItem()]);
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : "Unable to create requisition.");
    } finally {
      setSubmitting(false);
    }
  }

  if (!open) return null;
  return (
    <Modal title="New purchase requisition" subtitle={`${user?.department?.name ?? "Department"} · ${user?.department?.business_entity?.name ?? "Business entity"}`} close={close} large>
      <form onSubmit={submit} className="dialog-form">
        {error && <div className="form-alert">{error}</div>}
        <div className="form-grid"><label className="field"><span>Required date</span><input name="required_date" type="date" min={new Date().toISOString().slice(0, 10)} required /></label><label className="field wide"><span>Business purpose</span><textarea name="purpose" rows={3} required placeholder="Describe why this purchase is required" /></label></div>
        <div className="line-items-heading"><div><h3>Requested items</h3><p>Quantities and estimates establish the initial budget commitment.</p></div><button type="button" className="secondary-button compact" onClick={() => setItems((current) => [...current, emptyItem()])}><Plus size={15} />Add line</button></div>
        <div className="line-items">
          {items.map((item, index) => <div className="line-item" key={index}><div className="line-number">{index + 1}</div><div className="form-grid item-grid"><label className="field"><span>Item</span><input value={item.item_name} onChange={(event) => updateItem(index, "item_name", event.target.value)} required /></label><label className="field"><span>Specification</span><input value={item.specification} onChange={(event) => updateItem(index, "specification", event.target.value)} /></label><label className="field"><span>Quantity</span><input type="number" min="0.01" step="0.01" value={item.quantity} onChange={(event) => updateItem(index, "quantity", event.target.value)} required /></label><label className="field"><span>Unit</span><input value={item.unit} onChange={(event) => updateItem(index, "unit", event.target.value)} required placeholder="Each, box, kg" /></label><label className="field"><span>Estimated unit price</span><input type="number" min="0" step="0.01" value={item.estimated_unit_price} onChange={(event) => updateItem(index, "estimated_unit_price", event.target.value)} required /></label><div className="line-total"><span>Line total</span><strong>TZS {(Number(item.quantity || 0) * Number(item.estimated_unit_price || 0)).toLocaleString()}</strong></div></div>{items.length > 1 && <button type="button" className="icon-button danger" onClick={() => setItems((current) => current.filter((_, itemIndex) => itemIndex !== index))} title="Remove line"><Trash2 size={17} /></button>}</div>)}
        </div>
        <label className="field"><span>Estimate note <small>Optional</small></span><textarea name="estimate_difference_reason" rows={2} placeholder="Add context about the estimate where useful" /></label>
        <div className="requisition-total"><span>Estimated requisition total</span><strong>TZS {total.toLocaleString(undefined, { minimumFractionDigits: 2 })}</strong></div>
        <div className="dialog-actions"><button type="button" className="secondary-button" onClick={close}>Cancel</button><button className="primary-button" disabled={submitting || total <= 0}>{submitting && <LoaderCircle className="spin" size={16} />}Save draft</button></div>
      </form>
    </Modal>
  );
}

type ProformaItem = { item_name: string; specification: string; quantity: string; unit: string; unit_price: string; notes: string };
const emptyProformaItem = (): ProformaItem => ({ item_name: "", specification: "", quantity: "1", unit: "", unit_price: "", notes: "" });

export function ProformaDialog({ open, close, completed }: DialogProps) {
  const [requisitions, setRequisitions] = useState<JsonRecord[]>([]);
  const [suppliers, setSuppliers] = useState<JsonRecord[]>([]);
  const [items, setItems] = useState<ProformaItem[]>([emptyProformaItem()]);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState("");
  const total = useMemo(() => items.reduce((sum, item) => sum + Number(item.quantity || 0) * Number(item.unit_price || 0), 0), [items]);

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

  function updateItem(index: number, key: keyof ProformaItem, value: string) {
    setItems((current) => current.map((item, itemIndex) => itemIndex === index ? { ...item, [key]: value } : item));
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSubmitting(true);
    setError("");
    const form = new FormData(event.currentTarget);
    try {
      await api("admin/supplier-quotations", {
        method: "POST",
        body: JSON.stringify({
          purchase_requisition_id: Number(form.get("purchase_requisition_id")),
          supplier_id: Number(form.get("supplier_id")),
          quotation_number: form.get("quotation_number"),
          valid_until: form.get("valid_until") || null,
          notes: form.get("notes") || null,
          items: items.map((item) => ({ ...item, quantity: Number(item.quantity), unit_price: Number(item.unit_price) })),
        }),
      });
      completed("Supplier proforma created successfully.");
      setItems([emptyProformaItem()]);
      close();
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : "Unable to create proforma.");
    } finally {
      setSubmitting(false);
    }
  }

  if (!open) return null;
  return (
    <Modal title="New supplier proforma" subtitle="Associate the supplier offer with a sourcing-approved requisition." close={close} large>
      <form onSubmit={submit} className="dialog-form">
        {error && <div className="form-alert">{error}</div>}
        <div className="form-grid two">
          <label className="field"><span>Approved requisition</span><select name="purchase_requisition_id" required defaultValue=""><option value="" disabled>Select requisition</option>{requisitions.map((item) => <option key={String(item.id)} value={String(item.id)}>{String(item.requisition_number)} - {String(item.purpose)}</option>)}</select></label>
          <label className="field"><span>Supplier</span><select name="supplier_id" required defaultValue=""><option value="" disabled>Select supplier</option>{suppliers.map((supplier) => <option key={String(supplier.id)} value={String(supplier.id)}>{String(supplier.name)} ({String(supplier.code)})</option>)}</select></label>
          <Field label="Proforma number" name="quotation_number" required />
          <Field label="Valid until" name="valid_until" type="date" />
        </div>
        <div className="line-items-heading"><div><h3>Proforma items</h3><p>The total is calculated from the supplier&apos;s offered quantities and prices.</p></div><button type="button" className="secondary-button compact" onClick={() => setItems((current) => [...current, emptyProformaItem()])}><Plus size={15} />Add line</button></div>
        <div className="line-items">{items.map((item, index) => <div className="line-item" key={index}><div className="line-number">{index + 1}</div><div className="form-grid item-grid"><label className="field"><span>Item</span><input value={item.item_name} onChange={(event) => updateItem(index, "item_name", event.target.value)} required /></label><label className="field"><span>Specification</span><input value={item.specification} onChange={(event) => updateItem(index, "specification", event.target.value)} /></label><label className="field"><span>Quantity</span><input type="number" min="0.01" step="0.01" value={item.quantity} onChange={(event) => updateItem(index, "quantity", event.target.value)} required /></label><label className="field"><span>Unit</span><input value={item.unit} onChange={(event) => updateItem(index, "unit", event.target.value)} required /></label><label className="field"><span>Unit price</span><input type="number" min="0" step="0.01" value={item.unit_price} onChange={(event) => updateItem(index, "unit_price", event.target.value)} required /></label><div className="line-total"><span>Line total</span><strong>TZS {(Number(item.quantity || 0) * Number(item.unit_price || 0)).toLocaleString()}</strong></div></div>{items.length > 1 && <button type="button" className="icon-button danger" onClick={() => setItems((current) => current.filter((_, itemIndex) => itemIndex !== index))} title="Remove line"><Trash2 size={17} /></button>}</div>)}</div>
        <label className="field"><span>Supplier notes</span><textarea name="notes" rows={2} /></label>
        <div className="requisition-total"><span>Proforma total</span><strong>TZS {total.toLocaleString(undefined, { minimumFractionDigits: 2 })}</strong></div>
        <div className="dialog-actions"><button type="button" className="secondary-button" onClick={close}>Cancel</button><button className="primary-button" disabled={submitting || total <= 0 || requisitions.length === 0}>{submitting && <LoaderCircle className="spin" size={16} />}Save proforma</button></div>
      </form>
    </Modal>
  );
}

type PurchaseOrderLine = {
  id: number;
  item_name: string;
  quantity_ordered: number;
  quantity_received: number;
  unit: string;
  unit_price: number;
};

function purchaseOrderLines(order?: JsonRecord): PurchaseOrderLine[] {
  return ((order?.items as JsonRecord[] | undefined) ?? []).map((item) => ({
    id: Number(item.id),
    item_name: String(item.item_name ?? "Item"),
    quantity_ordered: Number(item.quantity_ordered ?? 0),
    quantity_received: Number(item.quantity_received ?? 0),
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
  const lines = purchaseOrderLines(order).filter((line) => line.quantity_received > 0);
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
    setQuantities(Object.fromEntries(lines.map((line) => [line.id, String(line.quantity_received)])));
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
        {order && <><div className="line-items-heading"><div><h3>Invoice lines</h3><p>Quantities cannot exceed product quantities accepted by the store.</p></div></div><div className="line-items">{lines.map((line, index) => <div className="line-item" key={line.id}><div className="line-number">{index + 1}</div><div className="form-grid item-grid"><div className="field"><span>Item</span><strong>{line.item_name}</strong></div><label className="field"><span>Quantity ({line.unit})</span><input type="number" min="0" max={line.quantity_received} step="0.01" value={quantities[line.id] ?? ""} onChange={(event) => setQuantities((current) => ({ ...current, [line.id]: event.target.value }))} /></label><label className="field"><span>Unit price</span><input type="number" min="0.01" step="0.01" value={prices[line.id] ?? ""} onChange={(event) => setPrices((current) => ({ ...current, [line.id]: event.target.value }))} /></label><div className="line-total"><span>Line total</span><strong>TZS {(Number(quantities[line.id] ?? 0) * Number(prices[line.id] ?? 0)).toLocaleString()}</strong></div></div></div>)}</div></>}
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

function Field({ label, name, type = "text", required = false, wide = false }: { label: string; name: string; type?: string; required?: boolean; wide?: boolean }) {
  return <label className={wide ? "field wide" : "field"}><span>{label}</span><input name={name} type={type} required={required} /></label>;
}
