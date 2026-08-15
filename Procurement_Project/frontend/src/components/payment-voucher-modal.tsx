"use client";

import { useAuth } from "@/components/auth-provider";
import { api, ApiError } from "@/lib/api";
import type { ActionSpec } from "@/lib/modules";
import type { JsonRecord } from "@/lib/types";
import { Building2, CalendarDays, FileText, LoaderCircle, Printer, ReceiptText, X } from "lucide-react";
import { useEffect, useMemo, useState } from "react";

type Props = {
  item: JsonRecord;
  actions: ActionSpec[];
  close: () => void;
  act: (action: ActionSpec, item: JsonRecord) => void;
};

export function PaymentVoucherModal({ item, actions, close, act }: Props) {
  const { user } = useAuth();
  const [voucher, setVoucher] = useState(item);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let active = true;
    setLoading(true);
    setError("");
    api<JsonRecord>(`admin/payment-vouchers/${Number(item.id)}`)
      .then((response) => {
        if (active) setVoucher((response.data as JsonRecord | undefined) ?? response);
      })
      .catch((caught) => {
        if (active) setError(caught instanceof ApiError ? caught.message : "Unable to load the complete voucher.");
      })
      .finally(() => {
        if (active) setLoading(false);
      });
    return () => { active = false; };
  }, [item]);

  const visibleActions = useMemo(
    () => user ? actions.filter((action) => action.visible(voucher, user)) : [],
    [actions, user, voucher],
  );
  const supplier = objectAt(voucher, "supplier");
  const invoice = objectAt(voucher, "supplier_invoice");
  const order = objectAt(invoice, "purchase_order");
  const requisition = objectAt(order, "requisition");
  const entity = objectAt(voucher, "business_entity");
  const financialYear = objectAt(voucher, "financial_year");
  const preparedBy = objectAt(voucher, "prepared_by");
  const approvedBy = objectAt(voucher, "approved_by");
  const paidBy = objectAt(voucher, "paid_by");
  const approvals = (voucher.approvals as JsonRecord[] | undefined) ?? [];
  const amount = Number(voucher.amount_approved || voucher.amount_requested || 0);

  return (
    <div className="payment-voucher-backdrop" role="presentation" onMouseDown={(event) => { if (event.target === event.currentTarget) close(); }}>
      <section className="payment-voucher-modal" role="dialog" aria-modal="true" aria-labelledby="voucher-title">
        <div className="voucher-toolbar">
          <div>
            <span>Payment voucher</span>
            <strong>{String(voucher.voucher_number ?? `Voucher #${voucher.id}`)}</strong>
          </div>
          <div className="voucher-toolbar-actions">
            {visibleActions.map((action) => <button key={action.label} className={action.tone === "danger" ? "danger-button compact" : action.tone === "primary" ? "primary-button compact" : "secondary-button compact"} onClick={() => act(action, voucher)}>{action.label}</button>)}
            <button className="icon-button bordered" onClick={() => window.print()} title="Print payment voucher"><Printer size={17} /></button>
            <button className="icon-button" onClick={close} title="Close payment voucher"><X size={19} /></button>
          </div>
        </div>

        {loading ? <div className="voucher-loading"><LoaderCircle className="spin" size={22} />Loading complete voucher...</div> : error ? <div className="voucher-error">{error}</div> : (
          <article className="payment-voucher-document">
            <header className="voucher-document-header">
              <div className="voucher-brand-mark"><ReceiptText size={27} /></div>
              <div className="voucher-entity">
                <p>{String(entity.name ?? "Procure Control Office")}</p>
                <span>{String(entity.code ?? "Procurement and Finance")}</span>
              </div>
              <div className="voucher-document-title">
                <p>Payment voucher</p>
                <strong id="voucher-title">{String(voucher.voucher_number ?? "Draft voucher")}</strong>
                <span className={`status status-${String(voucher.status ?? "draft").replaceAll("_", "-")}`}>{humanize(String(voucher.status ?? "draft"))}</span>
              </div>
            </header>

            <section className="voucher-meta-strip">
              <VoucherMeta icon={<CalendarDays size={16} />} label="Voucher date" value={formatDate(voucher.payment_date ?? voucher.created_at)} />
              <VoucherMeta icon={<FileText size={16} />} label="Supplier invoice" value={String(invoice.invoice_number ?? `#${voucher.supplier_invoice_id ?? "-"}`)} />
              <VoucherMeta icon={<Building2 size={16} />} label="Financial year" value={String(financialYear.name ?? financialYear.code ?? voucher.financial_year_id ?? "-")} />
            </section>

            <section className="voucher-payee-section">
              <div>
                <span className="voucher-label">Pay to</span>
                <h2>{String(supplier.name ?? "Supplier")}</h2>
                <p>{String(supplier.address ?? supplier.contact_person ?? "Approved supplier")}</p>
              </div>
              <div className="voucher-amount-box">
                <span>Amount authorized</span>
                <strong>{money(amount)}</strong>
              </div>
            </section>

            <section className="voucher-words">
              <span>Amount in words</span>
              <strong>{amountInWords(amount)}</strong>
            </section>

            <section className="voucher-particulars">
              <div className="voucher-section-heading"><h3>Payment particulars</h3><span>Supporting procurement references</span></div>
              <table>
                <thead><tr><th>Description</th><th>Reference</th><th className="numeric">Amount</th></tr></thead>
                <tbody>
                  <tr>
                    <td><strong>Settlement of supplier invoice</strong><span>{String(voucher.comments ?? invoice.notes ?? "Payment against verified procurement delivery.")}</span></td>
                    <td><strong>{String(invoice.invoice_number ?? "-")}</strong><span>{String(order.purchase_order_number ?? requisition.requisition_number ?? "No LPO reference")}</span></td>
                    <td className="numeric"><strong>{money(Number(voucher.amount_requested ?? amount))}</strong></td>
                  </tr>
                </tbody>
                <tfoot><tr><td colSpan={2}>Total payment</td><td className="numeric">{money(amount)}</td></tr></tfoot>
              </table>
            </section>

            <section className="voucher-payment-details">
              <VoucherField label="Payment method" value={humanize(String(voucher.payment_method ?? "Not specified"))} />
              <VoucherField label="Payment reference" value={String(voucher.payment_reference ?? "Pending")} />
              <VoucherField label="LPO number" value={String(order.purchase_order_number ?? "-")} />
              <VoucherField label="Requisition" value={String(requisition.requisition_number ?? "-")} />
            </section>

            {approvals.length > 0 && <section className="voucher-approval-history"><div className="voucher-section-heading"><h3>Authorization history</h3><span>{approvals.length} recorded action{approvals.length === 1 ? "" : "s"}</span></div>{approvals.map((approval) => <div key={String(approval.id)}><span>{humanize(String(approval.action ?? "action"))}</span><strong>{String(objectAt(approval, "actor").name ?? "System user")}</strong><time>{formatDateTime(approval.action_at ?? approval.created_at)}</time></div>)}</section>}

            <footer className="voucher-signatures">
              <SignatureBlock label="Prepared by" person={preparedBy} date={voucher.submitted_at ?? voucher.created_at} />
              <SignatureBlock label="Approved by" person={approvedBy} date={voucher.approved_at} />
              <SignatureBlock label="Payment released by" person={paidBy} date={voucher.paid_at} />
            </footer>

            <div className="voucher-document-footer">
              <span>System record #{String(voucher.id ?? "-")}</span>
              <span>Generated from Procure Control Office</span>
            </div>
          </article>
        )}
      </section>
    </div>
  );
}

function VoucherMeta({ icon, label, value }: { icon: React.ReactNode; label: string; value: string }) {
  return <div className="voucher-meta-item">{icon}<div><span>{label}</span><strong>{value}</strong></div></div>;
}

function VoucherField({ label, value }: { label: string; value: string }) {
  return <div><span>{label}</span><strong>{value}</strong></div>;
}

function SignatureBlock({ label, person, date }: { label: string; person: JsonRecord; date: unknown }) {
  return <div><span>{label}</span><strong>{String(person.name ?? "Pending")}</strong><p>{person.job_title ? String(person.job_title) : date ? formatDateTime(date) : "Awaiting authorization"}</p><i /></div>;
}

function objectAt(record: JsonRecord, key: string): JsonRecord {
  const value = record[key];
  return value && typeof value === "object" && !Array.isArray(value) ? value as JsonRecord : {};
}

function money(value: number) {
  return `TZS ${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function formatDate(value: unknown) {
  if (!value) return "Not set";
  const date = new Date(String(value));
  return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleDateString(undefined, { day: "2-digit", month: "short", year: "numeric" });
}

function formatDateTime(value: unknown) {
  if (!value) return "Pending";
  const date = new Date(String(value));
  return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString(undefined, { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" });
}

function humanize(value: string) {
  return value.replaceAll("_", " ").replace(/\b\w/g, (character) => character.toUpperCase());
}

function amountInWords(value: number) {
  const rounded = Math.floor(value);
  if (rounded === 0) return "Zero Tanzanian shillings only";
  return `${integerToWords(rounded)} Tanzanian shillings only`;
}

function integerToWords(value: number): string {
  const ones = ["", "one", "two", "three", "four", "five", "six", "seven", "eight", "nine", "ten", "eleven", "twelve", "thirteen", "fourteen", "fifteen", "sixteen", "seventeen", "eighteen", "nineteen"];
  const tens = ["", "", "twenty", "thirty", "forty", "fifty", "sixty", "seventy", "eighty", "ninety"];
  const underThousand = (number: number) => {
    const words: string[] = [];
    if (number >= 100) {
      words.push(ones[Math.floor(number / 100)], "hundred");
      number %= 100;
    }
    if (number >= 20) {
      words.push(tens[Math.floor(number / 10)]);
      number %= 10;
    }
    if (number > 0) words.push(ones[number]);
    return words.join(" ");
  };
  const groups = [[1_000_000_000, "billion"], [1_000_000, "million"], [1_000, "thousand"]] as const;
  const words: string[] = [];
  let remainder = value;
  for (const [size, name] of groups) {
    if (remainder >= size) {
      words.push(underThousand(Math.floor(remainder / size)), name);
      remainder %= size;
    }
  }
  if (remainder > 0) words.push(underThousand(remainder));
  const sentence = words.join(" ");
  return sentence.charAt(0).toUpperCase() + sentence.slice(1);
}
