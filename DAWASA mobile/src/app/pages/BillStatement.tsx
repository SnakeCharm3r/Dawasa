import { useState, useEffect, useMemo } from "react";
import { useNavigate } from "react-router";
import { Download, Mail, Filter, ChevronDown, ChevronUp, Droplets, TrendingDown, AlertCircle, Loader2, X, Send } from "lucide-react";
import { bills, session, type BillStatement as BillRow, type CustomerInfo } from "../../utils/supabase";
import { toast } from "sonner";

const MONTHS = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
const currentYear = new Date().getFullYear();
const YEARS = Array.from({ length: 5 }, (_, i) => currentYear - i);

export default function BillStatement() {
  const navigate = useNavigate();
  const [customer, setCustomer] = useState<CustomerInfo | null>(null);
  const [statements, setStatements] = useState<BillRow[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [filterYear, setFilterYear] = useState<number | undefined>(undefined);
  const [filterMonth, setFilterMonth] = useState<number | undefined>(undefined);
  const [showFilters, setShowFilters] = useState(false);
  const [showEmailModal, setShowEmailModal] = useState(false);
  const [email, setEmail] = useState("");
  const [isSendingEmail, setIsSendingEmail] = useState(false);
  const [expandedRow, setExpandedRow] = useState<string | null>(null);

  useEffect(() => {
    const info = session.getCustomer();
    const meterNo = session.getMeter();
    if (!info || !meterNo) { navigate("/", { replace: true }); return; }
    setCustomer(info);
    loadStatement(meterNo);
  }, [navigate]);

  const loadStatement = async (meterNo: string) => {
    setIsLoading(true);
    try {
      const data = await bills.getStatement(meterNo);
      setStatements(data);
    } catch (err: any) {
      toast.error(err.message || "Failed to load statement");
    } finally {
      setIsLoading(false);
    }
  };

  const filtered = useMemo(() => {
    return statements.filter(s => {
      if (filterYear && s.year !== filterYear) return false;
      if (filterMonth && s.month !== filterMonth) return false;
      return true;
    });
  }, [statements, filterYear, filterMonth]);

  const totals = useMemo(() => ({
    totalDebit: filtered.filter(s => s.type === "bill").reduce((a, s) => a + s.debit, 0),
    totalCredit: filtered.filter(s => s.type === "payment").reduce((a, s) => a + s.credit, 0),
    totalConsumption: filtered.reduce((a, s) => a + (s.consumption ?? 0), 0),
    pendingBalance: filtered.length > 0 ? filtered[0].closingBalance : 0,
  }), [filtered]);

  const handleDownloadCSV = () => {
    if (!customer) return;
    const csv = bills.generateCSV(filtered, customer);
    const blob = new Blob([csv], { type: "text/csv" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `DAWASA_Statement_${customer.meterNumber}_${filterYear ?? "All"}.csv`;
    a.click();
    URL.revokeObjectURL(url);
    toast.success("Statement downloaded as CSV");
  };

  const handleSendEmail = async () => {
    if (!email.includes("@")) { toast.error("Please enter a valid email address"); return; }
    if (!customer) return;
    setIsSendingEmail(true);
    try {
      await bills.exportEmail(customer.meterNumber, email, {
        year: filterYear,
        month: filterMonth,
      });
      toast.success(`Statement sent to ${email}`);
      setShowEmailModal(false);
      setEmail("");
    } catch (err: any) {
      toast.error(err.message || "Failed to send email");
    } finally {
      setIsSendingEmail(false);
    }
  };

  if (isLoading) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[60vh] gap-3">
        <Loader2 className="w-10 h-10 text-white animate-spin" />
        <p className="text-white/80 font-medium">Loading statement…</p>
      </div>
    );
  }

  return (
    <div className="pb-6">
      {/* Customer Info Card */}
      {customer && (
        <div className="mx-4 mt-4 bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20">
          <div className="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
            <div>
              <span className="text-white/50 text-xs">Customer</span>
              <p className="text-white font-bold leading-tight">{customer.customerName}</p>
            </div>
            <div>
              <span className="text-white/50 text-xs">Account No.</span>
              <p className="text-white font-bold">{customer.accountNumber}</p>
            </div>
            <div>
              <span className="text-white/50 text-xs">Meter No.</span>
              <p className="text-[#00AEEF] font-bold">{customer.meterNumber}</p>
            </div>
            <div>
              <span className="text-white/50 text-xs">Zone / Route</span>
              <p className="text-white font-semibold">{customer.zone} / {customer.route}</p>
            </div>
            <div>
              <span className="text-white/50 text-xs">Category</span>
              <p className="text-white font-semibold">{customer.category}</p>
            </div>
            {customer.controlNumber && (
              <div>
                <span className="text-white/50 text-xs">Control No.</span>
                <p className="text-yellow-300 font-bold text-xs">{customer.controlNumber}</p>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Summary Tiles */}
      <div className="grid grid-cols-3 gap-2 mx-4 mt-3">
        <div className="bg-white/10 rounded-2xl p-3 border border-white/20 text-center">
          <p className="text-white/50 text-[10px] uppercase font-bold mb-1">Balance</p>
          <p className="text-white font-black text-sm leading-tight">{totals.pendingBalance.toLocaleString()}</p>
          <p className="text-white/40 text-[10px]">TZS</p>
        </div>
        <div className="bg-white/10 rounded-2xl p-3 border border-white/20 text-center">
          <p className="text-white/50 text-[10px] uppercase font-bold mb-1">Billed</p>
          <p className="text-[#00AEEF] font-black text-sm leading-tight">{totals.totalDebit.toLocaleString()}</p>
          <p className="text-white/40 text-[10px]">TZS</p>
        </div>
        <div className="bg-white/10 rounded-2xl p-3 border border-white/20 text-center">
          <p className="text-white/50 text-[10px] uppercase font-bold mb-1">Usage</p>
          <p className="text-[#00A651] font-black text-sm leading-tight">{totals.totalConsumption.toFixed(1)}</p>
          <p className="text-white/40 text-[10px]">m³</p>
        </div>
      </div>

      {/* Filter + Export Bar */}
      <div className="mx-4 mt-3 flex items-center gap-2">
        <button
          onClick={() => setShowFilters(!showFilters)}
          className="flex items-center gap-1.5 bg-white/10 border border-white/20 rounded-xl px-3 py-2 text-white text-xs font-semibold flex-1"
        >
          <Filter className="w-3.5 h-3.5" />
          {filterYear ? `${filterYear}` : "All years"}
          {filterMonth ? ` · ${MONTHS[filterMonth - 1]}` : ""}
          {showFilters ? <ChevronUp className="w-3.5 h-3.5 ml-auto" /> : <ChevronDown className="w-3.5 h-3.5 ml-auto" />}
        </button>
        <button onClick={handleDownloadCSV} aria-label="Download CSV" className="bg-white/10 border border-white/20 rounded-xl p-2.5 text-white">
          <Download className="w-4 h-4" />
        </button>
        <button onClick={() => setShowEmailModal(true)} aria-label="Email statement" className="bg-white/10 border border-white/20 rounded-xl p-2.5 text-white">
          <Mail className="w-4 h-4" />
        </button>
      </div>

      {/* Filter Panel */}
      {showFilters && (
        <div className="mx-4 mt-2 bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20">
          <div className="mb-3">
            <p className="text-white/60 text-xs font-bold uppercase mb-2">Year</p>
            <div className="flex flex-wrap gap-2">
              <button onClick={() => setFilterYear(undefined)} className={`px-3 py-1.5 rounded-xl text-xs font-bold ${!filterYear ? "bg-white text-[#0057A8]" : "bg-white/10 text-white border border-white/20"}`}>All</button>
              {YEARS.map(y => (
                <button key={y} onClick={() => setFilterYear(y)} className={`px-3 py-1.5 rounded-xl text-xs font-bold ${filterYear === y ? "bg-white text-[#0057A8]" : "bg-white/10 text-white border border-white/20"}`}>{y}</button>
              ))}
            </div>
          </div>
          <div>
            <p className="text-white/60 text-xs font-bold uppercase mb-2">Month</p>
            <div className="flex flex-wrap gap-2">
              <button onClick={() => setFilterMonth(undefined)} className={`px-3 py-1.5 rounded-xl text-xs font-bold ${!filterMonth ? "bg-white text-[#0057A8]" : "bg-white/10 text-white border border-white/20"}`}>All</button>
              {MONTHS.map((m, i) => (
                <button key={m} onClick={() => setFilterMonth(i + 1)} className={`px-3 py-1.5 rounded-xl text-xs font-bold ${filterMonth === i + 1 ? "bg-white text-[#0057A8]" : "bg-white/10 text-white border border-white/20"}`}>{m}</button>
              ))}
            </div>
          </div>
          {(filterYear || filterMonth) && (
            <button onClick={() => { setFilterYear(undefined); setFilterMonth(undefined); }} className="mt-3 text-[#00AEEF] text-xs font-semibold flex items-center gap-1">
              <X className="w-3.5 h-3.5" /> Clear filters
            </button>
          )}
        </div>
      )}

      {/* Statement Table */}
      <div className="mx-4 mt-3 space-y-2">
        {filtered.length === 0 ? (
          <div className="bg-white/10 rounded-2xl p-8 text-center border border-white/20">
            <AlertCircle className="w-10 h-10 text-white/30 mx-auto mb-2" />
            <p className="text-white/60">No records for this period</p>
          </div>
        ) : (
          filtered.map((row) => {
            const isExpanded = expandedRow === row.id;
            const isPayment = row.type === "payment";
            return (
              <div key={row.id} className="bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 overflow-hidden">
                {/* Row Header */}
                <button
                  onClick={() => setExpandedRow(isExpanded ? null : row.id)}
                  className="w-full p-4 text-left"
                >
                  <div className="flex items-start justify-between gap-2">
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2 mb-1">
                        <span className={`text-[10px] font-bold uppercase px-2 py-0.5 rounded-full ${isPayment ? "bg-[#00A651]/20 text-[#00A651]" : "bg-[#00AEEF]/20 text-[#00AEEF]"}`}>
                          {isPayment ? "Payment" : "Bill"}
                        </span>
                        <span className="text-white/40 text-[10px]">{row.date}</span>
                      </div>
                      <p className="text-white font-bold text-sm leading-snug truncate">{row.reference}</p>
                      <p className="text-white/50 text-xs mt-0.5 line-clamp-1">{row.description}</p>
                    </div>
                    <div className="text-right flex-shrink-0">
                      {isPayment ? (
                        <p className="text-[#00A651] font-black text-sm">+{row.credit.toLocaleString()}</p>
                      ) : (
                        <p className="text-[#00AEEF] font-black text-sm">{row.debit.toLocaleString()}</p>
                      )}
                      <p className="text-white/40 text-[10px]">TZS</p>
                      {isExpanded ? <ChevronUp className="w-3.5 h-3.5 text-white/40 ml-auto mt-1" /> : <ChevronDown className="w-3.5 h-3.5 text-white/40 ml-auto mt-1" />}
                    </div>
                  </div>
                </button>

                {/* Expanded Details */}
                {isExpanded && (
                  <div className="border-t border-white/10 bg-black/10 p-4 space-y-3">
                    <div className="grid grid-cols-2 gap-3">
                      <div>
                        <p className="text-white/40 text-[10px] uppercase mb-0.5">Opening Balance</p>
                        <p className="text-white font-semibold text-sm">{row.openingBalance.toLocaleString()} TZS</p>
                      </div>
                      <div>
                        <p className="text-white/40 text-[10px] uppercase mb-0.5">Closing Balance</p>
                        <p className="text-white font-semibold text-sm">{row.closingBalance.toLocaleString()} TZS</p>
                      </div>
                    </div>
                    {row.openingReading !== undefined && (
                      <div className="bg-white/5 rounded-xl p-3">
                        <p className="text-white/50 text-[10px] uppercase font-bold mb-2 flex items-center gap-1"><Droplets className="w-3 h-3" /> Meter Readings</p>
                        <div className="grid grid-cols-3 gap-2 text-center">
                          <div>
                            <p className="text-white/40 text-[10px]">Previous</p>
                            <p className="text-white font-bold">{row.openingReading} m³</p>
                          </div>
                          <div>
                            <p className="text-white/40 text-[10px]">Current</p>
                            <p className="text-white font-bold">{row.closingReading} m³</p>
                          </div>
                          <div>
                            <p className="text-white/40 text-[10px]">Used</p>
                            <p className="text-[#00A651] font-black flex items-center justify-center gap-0.5"><TrendingDown className="w-3 h-3" />{row.consumption} m³</p>
                          </div>
                        </div>
                      </div>
                    )}
                    <div className="flex gap-2">
                      <span className={`text-xs font-bold px-3 py-1 rounded-full ${
                        row.status === "Paid" ? "bg-[#00A651]/20 text-[#00A651]" :
                        row.status === "Partial" ? "bg-yellow-400/20 text-yellow-300" :
                        "bg-red-400/20 text-red-300"
                      }`}>{row.status}</span>
                    </div>
                  </div>
                )}
              </div>
            );
          })
        )}
      </div>

      {/* Email Modal */}
      {showEmailModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-end">
          <div className="w-full bg-white rounded-t-3xl p-6 pb-10">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-black text-[#003F7F]">Email Statement</h3>
              <button onClick={() => setShowEmailModal(false)} aria-label="Close" className="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                <X className="w-4 h-4 text-gray-600" />
              </button>
            </div>
            <p className="text-sm text-gray-500 mb-4">
              We'll send a consolidated PDF report{filterYear ? ` for ${filterYear}` : ""} to your email.
            </p>
            <input
              type="email"
              placeholder="your@email.com"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full h-14 px-4 rounded-2xl border-2 border-gray-200 focus:border-[#0057A8] focus:outline-none text-base mb-4"
            />
            <button
              onClick={handleSendEmail}
              disabled={isSendingEmail}
              className="w-full h-14 bg-gradient-to-r from-[#0057A8] to-[#00AEEF] text-white rounded-2xl font-black flex items-center justify-center gap-2 disabled:opacity-70"
            >
              {isSendingEmail ? <Loader2 className="w-5 h-5 animate-spin" /> : <Send className="w-5 h-5" />}
              {isSendingEmail ? "Sending…" : "Send Report"}
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
