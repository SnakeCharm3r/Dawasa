import { useState, useEffect } from "react";
import { useNavigate } from "react-router";
import { Receipt, Trash2, Share2, Copy, Check, AlertCircle } from "lucide-react";
import { session, type Receipt as ReceiptType } from "../../utils/supabase";
import { toast } from "sonner";

export default function Receipts() {
  const navigate = useNavigate();
  const [receipts, setReceipts] = useState<ReceiptType[]>([]);
  const [copied, setCopied] = useState<string | null>(null);

  useEffect(() => {
    const mn = session.getMeter();
    if (!mn) { navigate("/", { replace: true }); return; }
    setReceipts(session.getReceipts());
  }, [navigate]);

  const handleCopy = async (text: string, id: string) => {
    await navigator.clipboard.writeText(text);
    setCopied(id);
    setTimeout(() => setCopied(null), 2000);
    toast.success("Copied!");
  };

  const handleShare = async (r: ReceiptType) => {
    const text =
      `DAWASA Payment Receipt\n` +
      `─────────────────────\n` +
      `Meter No:    ${r.meterNumber}\n` +
      `Control No:  ${r.controlNumber}\n` +
      `Amount:      ${r.amount.toLocaleString()} TZS\n` +
      `Method:      ${r.method}\n` +
      `Reference:   ${r.reference}\n` +
      `Date:        ${new Date(r.paidAt).toLocaleString()}\n` +
      `─────────────────────\n` +
      `Dar-es-Salaam Water Supply and Sanitation Authority`;

    if (navigator.share) {
      try {
        await navigator.share({ title: "DAWASA Receipt", text });
      } catch {
        // user cancelled
      }
    } else {
      await navigator.clipboard.writeText(text);
      toast.success("Receipt copied to clipboard");
    }
  };

  const handleClear = () => {
    localStorage.removeItem("dawasa_receipts");
    setReceipts([]);
    toast.success("Receipts cleared");
  };

  return (
    <div className="pb-8 px-4 pt-4 space-y-4">

      {/* Header row */}
      <div className="flex items-center justify-between">
        <p className="text-white/60 text-xs font-bold uppercase">
          {receipts.length} receipt{receipts.length !== 1 ? "s" : ""} saved on device
        </p>
        {receipts.length > 0 && (
          <button
            onClick={handleClear}
            className="flex items-center gap-1.5 text-red-300/70 text-xs font-semibold"
          >
            <Trash2 className="w-3.5 h-3.5" /> Clear all
          </button>
        )}
      </div>

      {/* Empty state */}
      {receipts.length === 0 && (
        <div className="bg-white/10 border border-white/20 rounded-2xl p-10 text-center">
          <Receipt className="w-12 h-12 text-white/20 mx-auto mb-3" />
          <p className="text-white/60 font-semibold mb-1">No receipts yet</p>
          <p className="text-white/40 text-sm">
            After confirming a payment, the receipt will be saved here on your device.
          </p>
        </div>
      )}

      {/* Receipt Cards */}
      {receipts.map((r) => (
        <div key={r.id} className="bg-white/10 border border-white/20 rounded-2xl overflow-hidden">
          {/* Green top bar */}
          <div className="bg-[#00A651]/20 border-b border-white/10 px-4 py-2 flex items-center justify-between">
            <div className="flex items-center gap-2">
              <div className="w-5 h-5 rounded-full bg-[#00A651]/30 flex items-center justify-center">
                <Check className="w-3 h-3 text-[#00A651]" />
              </div>
              <span className="text-[#00A651] text-xs font-bold uppercase">Payment Confirmed</span>
            </div>
            <span className="text-white/40 text-[10px]">{new Date(r.paidAt).toLocaleDateString()}</span>
          </div>

          {/* Body */}
          <div className="p-4 space-y-3">
            {/* Amount */}
            <div className="text-center py-2">
              <p className="text-white/50 text-xs uppercase mb-1">Amount Paid</p>
              <p className="text-white font-black text-3xl">{r.amount.toLocaleString()}</p>
              <p className="text-white/40 text-xs">TZS</p>
            </div>

            {/* Details grid */}
            <div className="space-y-2 text-sm">
              {[
                { label: "Meter No.",     value: r.meterNumber },
                { label: "Method",        value: r.method },
                { label: "Reference",     value: r.reference },
                { label: "Date & Time",   value: new Date(r.paidAt).toLocaleString() },
              ].map(({ label, value }) => (
                <div key={label} className="flex items-center justify-between">
                  <span className="text-white/40 text-xs">{label}</span>
                  <span className="text-white font-semibold text-xs">{value}</span>
                </div>
              ))}
            </div>

            {/* Control Number with copy */}
            <div className="bg-black/20 rounded-xl px-4 py-3 flex items-center justify-between">
              <div>
                <p className="text-white/40 text-[10px] uppercase mb-0.5">Control Number</p>
                <p className="text-white font-mono font-black text-base tracking-widest">{r.controlNumber}</p>
              </div>
              <button
                onClick={() => handleCopy(r.controlNumber, r.id)}
                aria-label="Copy control number"
                className="p-2 rounded-lg bg-white/10"
              >
                {copied === r.id
                  ? <Check className="w-4 h-4 text-[#00A651]" />
                  : <Copy className="w-4 h-4 text-white/60" />}
              </button>
            </div>

            {/* Share */}
            <button
              onClick={() => handleShare(r)}
              className="w-full flex items-center justify-center gap-2 py-3 bg-white/5 border border-white/15 rounded-xl text-white/70 text-sm font-semibold"
            >
              <Share2 className="w-4 h-4" /> Share Receipt
            </button>
          </div>
        </div>
      ))}

      {/* Note */}
      <div className="bg-yellow-400/10 border border-yellow-400/20 rounded-2xl p-4 flex items-start gap-3">
        <AlertCircle className="w-4 h-4 text-yellow-300 flex-shrink-0 mt-0.5" />
        <p className="text-yellow-200/70 text-xs leading-relaxed">
          Receipts are stored only on this device. Clearing your browser data will remove them. Share or screenshot important receipts for your records.
        </p>
      </div>
    </div>
  );
}
