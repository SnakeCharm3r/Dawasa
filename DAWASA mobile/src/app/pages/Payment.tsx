import { useState, useEffect } from "react";
import { useNavigate } from "react-router";
import { CreditCard, Copy, Check, AlertCircle, Smartphone, Building2, Users, Phone, Loader2, RefreshCw, Bell, CheckCircle2, X } from "lucide-react";
import { payments, session, type PaymentControlNumber, type Receipt } from "../../utils/supabase";
import { toast } from "sonner";

const CARRIERS = [
  { id: "mpesa",   label: "M-Pesa",       ussd: "*150*00#", icon: "🟢" },
  { id: "tigo",    label: "Tigo Pesa",    ussd: "*150*01#", icon: "🔵" },
  { id: "airtel",  label: "Airtel Money", ussd: "*150*60#", icon: "🔴" },
  { id: "halopesa",label: "HaloPesa",     ussd: "*150*88#", icon: "🟡" },
];

export default function Payment() {
  const navigate = useNavigate();
  const [meterNumber, setMeterNumber] = useState<string>("");
  const [existingCN, setExistingCN] = useState<PaymentControlNumber | null>(null);
  const [amount, setAmount] = useState("");
  const [paymentMethod, setPaymentMethod] = useState<"mobile" | "bank" | "agent">("mobile");
  const [selectedCarrier, setSelectedCarrier] = useState(CARRIERS[0]);
  const [isLoading, setIsLoading] = useState(true);
  const [isGenerating, setIsGenerating] = useState(false);
  const [generatedCN, setGeneratedCN] = useState<PaymentControlNumber | null>(null);
  const [showCarrierPrompt, setShowCarrierPrompt] = useState(false);
  const [copied, setCopied] = useState(false);

  useEffect(() => {
    const mn = session.getMeter();
    if (!mn) { navigate("/", { replace: true }); return; }
    setMeterNumber(mn);
    loadExistingControlNumber(mn);
  }, [navigate]);

  const loadExistingControlNumber = async (mn: string) => {
    setIsLoading(true);
    try {
      const cn = await payments.getControlNumber(mn);
      setExistingCN(cn);
      if (cn) setAmount(String(cn.amount));
    } catch {
      // no existing CN is fine
    } finally {
      setIsLoading(false);
    }
  };

  const handleGenerate = async () => {
    const amt = parseFloat(amount);
    if (!amount || isNaN(amt) || amt <= 0) {
      toast.error("Please enter a valid amount");
      return;
    }
    setIsGenerating(true);
    try {
      const cn = await payments.generateControlNumber(meterNumber, amt);
      setGeneratedCN(cn);
      setExistingCN(cn);
      toast.success("Control number ready!");
      if (paymentMethod === "mobile") setShowCarrierPrompt(true);
    } catch (err: any) {
      toast.error(err.message || "Failed to generate control number");
    } finally {
      setIsGenerating(false);
    }
  };

  const handleCopy = async (text: string) => {
    await navigator.clipboard.writeText(text);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
    toast.success("Copied!");
  };

  const handleConfirmPayment = async () => {
    const activeCN = generatedCN ?? existingCN;
    if (!activeCN) return;
    try {
      const receipt = await payments.confirmPayment(activeCN.controlNumber, meterNumber);
      session.saveReceipt(receipt);
      toast.success("Payment confirmed! Receipt saved.");
      navigate("/receipts");
    } catch (err: any) {
      toast.error(err.message || "Payment not yet confirmed in system");
    }
  };

  const activeCN = generatedCN ?? existingCN;

  if (isLoading) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[60vh] gap-3">
        <Loader2 className="w-10 h-10 text-white animate-spin" />
        <p className="text-white/80 font-medium">Checking payment status…</p>
      </div>
    );
  }

  return (
    <div className="pb-8 space-y-4 px-4 pt-4">

      {/* Active Control Number Banner */}
      {activeCN && activeCN.status === "active" && (
        <div className="bg-white/10 border border-white/20 rounded-2xl p-4">
          <div className="flex items-center gap-2 mb-2">
            <div className="w-7 h-7 rounded-full bg-[#00A651]/20 flex items-center justify-center">
              <CheckCircle2 className="w-4 h-4 text-[#00A651]" />
            </div>
            <p className="text-white font-bold text-sm">Active Control Number</p>
            <span className="ml-auto text-[10px] text-[#00A651] font-bold bg-[#00A651]/20 px-2 py-0.5 rounded-full">ACTIVE</span>
          </div>
          <div className="flex items-center justify-between bg-white/10 rounded-xl px-4 py-3">
            <span className="text-white font-mono font-black text-lg tracking-widest">{activeCN.controlNumber}</span>
            <button
              onClick={() => handleCopy(activeCN.controlNumber)}
              aria-label="Copy control number"
              className="p-2 rounded-lg bg-white/10 text-white"
            >
              {copied ? <Check className="w-4 h-4 text-[#00A651]" /> : <Copy className="w-4 h-4" />}
            </button>
          </div>
          <div className="flex justify-between mt-2 text-xs text-white/50">
            <span>Amount: <span className="text-white font-semibold">{activeCN.amount.toLocaleString()} TZS</span></span>
            <span>Expires: {new Date(activeCN.expiresAt).toLocaleDateString()}</span>
          </div>
        </div>
      )}

      {/* Payment Method Tabs */}
      <div className="grid grid-cols-3 gap-2">
        {(["mobile", "bank", "agent"] as const).map((m) => (
          <button
            key={m}
            onClick={() => setPaymentMethod(m)}
            className={`py-3 rounded-2xl border-2 flex flex-col items-center gap-1 transition-all ${
              paymentMethod === m ? "border-white bg-white/20" : "border-white/20 bg-white/5"
            }`}
          >
            {m === "mobile" && <Smartphone className={`w-5 h-5 ${paymentMethod === m ? "text-white" : "text-white/50"}`} />}
            {m === "bank"   && <Building2  className={`w-5 h-5 ${paymentMethod === m ? "text-white" : "text-white/50"}`} />}
            {m === "agent"  && <Users       className={`w-5 h-5 ${paymentMethod === m ? "text-white" : "text-white/50"}`} />}
            <span className={`text-[11px] font-bold ${
              paymentMethod === m ? "text-white" : "text-white/50"
            }`}>{m === "mobile" ? "Mobile" : m === "bank" ? "Bank" : "Agent"}</span>
          </button>
        ))}
      </div>

      {/* Amount Input + Generate */}
      <div className="bg-white/10 border border-white/20 rounded-2xl p-4 space-y-4">
        <div>
          <label className="block text-white/60 text-xs font-bold uppercase mb-1.5">Amount (TZS)</label>
          <input
            type="number"
            inputMode="numeric"
            placeholder="e.g. 45000"
            value={amount}
            onChange={(e) => setAmount(e.target.value)}
            className="w-full h-14 px-4 rounded-xl bg-white/10 border border-white/20 text-white text-xl font-black placeholder-white/30 focus:outline-none focus:border-white/60"
          />
        </div>

        {paymentMethod === "mobile" && (
          <div>
            <label className="block text-white/60 text-xs font-bold uppercase mb-1.5">Carrier</label>
            <div className="grid grid-cols-2 gap-2">
              {CARRIERS.map((c) => (
                <button
                  key={c.id}
                  onClick={() => setSelectedCarrier(c)}
                  className={`flex items-center gap-2 px-3 py-2.5 rounded-xl border text-sm font-semibold transition-all ${
                    selectedCarrier.id === c.id
                      ? "border-white bg-white text-[#003F7F]"
                      : "border-white/20 text-white/70"
                  }`}
                >
                  <span>{c.icon}</span> {c.label}
                </button>
              ))}
            </div>
          </div>
        )}

        <button
          onClick={handleGenerate}
          disabled={isGenerating}
          className="w-full h-14 bg-white text-[#003F7F] rounded-2xl font-black text-base shadow-lg active:scale-95 transition-all flex items-center justify-center gap-2 disabled:opacity-60"
        >
          {isGenerating
            ? <><Loader2 className="w-5 h-5 animate-spin" /> Generating…</>
            : <><CreditCard className="w-5 h-5" /> Get Control Number</>}
        </button>
      </div>

      {/* How to Pay Instructions */}
      {activeCN && (
        <div className="bg-white/10 border border-white/20 rounded-2xl p-4 space-y-3">
          <p className="text-white font-bold text-sm flex items-center gap-2">
            <Phone className="w-4 h-4 text-[#00AEEF]" /> How to Pay
          </p>
          {paymentMethod === "mobile" ? (
            <>
              <div className="bg-black/20 rounded-xl p-3">
                <p className="text-white/50 text-[10px] uppercase mb-1">USSD Code — {selectedCarrier.label}</p>
                <p className="text-[#00AEEF] font-mono font-black text-2xl">{selectedCarrier.ussd}</p>
              </div>
              <ol className="space-y-1.5 text-sm text-white/80">
                <li className="flex gap-2"><span className="text-[#00AEEF] font-bold">1.</span> Dial {selectedCarrier.ussd} on your phone</li>
                <li className="flex gap-2"><span className="text-[#00AEEF] font-bold">2.</span> Select <em>Pay Bill</em> or <em>Lipa na Nambari ya Udhibiti</em></li>
                <li className="flex gap-2"><span className="text-[#00AEEF] font-bold">3.</span> Enter control number: <span className="font-mono font-bold text-white">{activeCN.controlNumber}</span></li>
                <li className="flex gap-2"><span className="text-[#00AEEF] font-bold">4.</span> Confirm amount: <span className="font-bold text-white">{activeCN.amount.toLocaleString()} TZS</span></li>
                <li className="flex gap-2"><span className="text-[#00AEEF] font-bold">5.</span> Enter your PIN to complete</li>
              </ol>
              <button
                onClick={() => setShowCarrierPrompt(true)}
                className="w-full flex items-center justify-center gap-2 py-3 bg-[#00AEEF]/20 border border-[#00AEEF]/30 rounded-xl text-[#00AEEF] font-bold text-sm"
              >
                <Bell className="w-4 h-4" /> Send Payment Reminder
              </button>
            </>
          ) : paymentMethod === "bank" ? (
            <div className="space-y-2 text-sm text-white/80">
              <p>Transfer to DAWASA account at CRDB, NMB, or NBC using control number as reference.</p>
              <p className="font-mono text-white font-bold">{activeCN.controlNumber}</p>
            </div>
          ) : (
            <p className="text-sm text-white/80">Visit any DAWASA Service Centre and provide control number <span className="font-mono font-bold text-white">{activeCN.controlNumber}</span>.</p>
          )}
        </div>
      )}

      {/* Confirm Payment */}
      {activeCN && (
        <button
          onClick={handleConfirmPayment}
          className="w-full h-14 bg-gradient-to-r from-[#00A651] to-[#00c562] text-white rounded-2xl font-black text-base shadow-lg active:scale-95 transition-all flex items-center justify-center gap-2"
        >
          <CheckCircle2 className="w-5 h-5" /> I've Paid — Save Receipt
        </button>
      )}

      {/* Helpline */}
      <div className="bg-white/5 border border-white/10 rounded-2xl p-4">
        <p className="text-white/40 text-xs font-bold uppercase mb-2">Need Help?</p>
        <div className="space-y-1.5">
          {[
            { icon: Smartphone, label: "M-Pesa / Tigo / Airtel / HaloPesa" },
            { icon: Building2,  label: "CRDB · NMB · NBC Bank Transfer" },
            { icon: Users,      label: "DAWASA Service Centres" },
          ].map(({ icon: Icon, label }) => (
            <div key={label} className="flex items-center gap-2 text-white/60 text-xs">
              <Icon className="w-3.5 h-3.5 flex-shrink-0" />{label}
            </div>
          ))}
          <p className="text-[#00AEEF] font-bold text-sm pt-1">+255 22 245 3511</p>
          <p className="text-white/30 text-[10px]">Mon–Fri · 8AM–5PM</p>
        </div>
      </div>

      {/* Carrier Prompt Modal */}
      {showCarrierPrompt && activeCN && (
        <div className="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-end">
          <div className="w-full bg-white rounded-t-3xl p-6 pb-10">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-black text-[#003F7F]">Pay via {selectedCarrier.label}</h3>
              <button
                onClick={() => setShowCarrierPrompt(false)}
                aria-label="Close"
                className="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center"
              >
                <X className="w-4 h-4 text-gray-600" />
              </button>
            </div>
            <div className="bg-[#E8F1FB] rounded-2xl p-4 mb-4 text-center">
              <p className="text-[#003F7F] text-xs font-bold uppercase mb-1">Control Number</p>
              <p className="text-[#0057A8] font-mono font-black text-3xl tracking-widest">{activeCN.controlNumber}</p>
              <p className="text-gray-500 text-sm mt-1">{activeCN.amount.toLocaleString()} TZS</p>
            </div>
            <p className="text-center text-gray-600 text-sm mb-4">
              Dial <span className="font-mono font-black text-[#0057A8]">{selectedCarrier.ussd}</span> on your phone, then enter the control number above.
            </p>
            <button
              onClick={() => { handleCopy(activeCN.controlNumber); setShowCarrierPrompt(false); }}
              className="w-full h-14 bg-gradient-to-r from-[#0057A8] to-[#00AEEF] text-white rounded-2xl font-black flex items-center justify-center gap-2"
            >
              <Copy className="w-5 h-5" /> Copy &amp; Close
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
