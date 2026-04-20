import { useState } from "react";
import { useNavigate } from "react-router";
import { Droplet, Search, AlertCircle, Loader2, MapPin, User, Hash, Tag } from "lucide-react";
import { meter, session } from "../../utils/supabase";
import { toast } from "sonner";

export default function Home() {
  const navigate = useNavigate();
  const [meterNumber, setMeterNumber] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState("");

  const handleLookup = async (e: React.FormEvent) => {
    e.preventDefault();
    const trimmed = meterNumber.trim();
    if (!trimmed) {
      setError("Please enter your water meter number.");
      return;
    }
    setError("");
    setIsLoading(true);
    try {
      const customer = await meter.lookup(trimmed);
      session.saveMeter(trimmed, customer);
      toast.success(`Welcome, ${customer.customerName.split(" ")[0]}!`);
      navigate("/statement", { replace: true });
    } catch (err: any) {
      setError(err.message || "Meter number not found. Please check and try again.");
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-[#003F7F] via-[#0057A8] to-[#005f8e] flex flex-col">
      {/* Safe area top */}
      <div className="h-12 bg-transparent" />

      {/* Hero */}
      <div className="flex-1 flex flex-col items-center justify-center px-6 pb-10">
        {/* Logo */}
        <div className="flex flex-col items-center mb-10">
          <div className="bg-white/10 backdrop-blur-md p-7 rounded-[2rem] mb-5 shadow-2xl border border-white/20">
            <Droplet className="w-16 h-16 text-white" fill="white" />
          </div>
          <h1 className="text-4xl font-black text-white tracking-tight mb-1">DAWASA</h1>
          <p className="text-[#00AEEF] text-sm font-semibold text-center leading-snug">
            Dar-es-Salaam Water Supply{"\n"}and Sanitation Authority
          </p>
        </div>

        {/* Card */}
        <div className="w-full max-w-sm bg-white rounded-3xl shadow-2xl p-6">
          <h2 className="text-xl font-black text-[#003F7F] mb-1">Check Your Bill</h2>
          <p className="text-sm text-gray-500 mb-6">
            Enter your water meter number to access your bill statement — no account needed.
          </p>

          <form onSubmit={handleLookup} className="space-y-4">
            <div>
              <label htmlFor="meter" className="block text-xs font-bold text-gray-600 mb-1.5 uppercase tracking-wide">
                Meter Number
              </label>
              <div className="relative">
                <Hash className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input
                  id="meter"
                  type="text"
                  placeholder="e.g. 23-15-17312"
                  value={meterNumber}
                  onChange={(e) => { setMeterNumber(e.target.value); setError(""); }}
                  className="w-full h-14 pl-11 pr-4 rounded-2xl border-2 border-gray-200 focus:border-[#0057A8] focus:outline-none text-base font-semibold text-gray-800 bg-gray-50 transition-colors"
                  autoComplete="off"
                  inputMode="text"
                  disabled={isLoading}
                />
              </div>
            </div>

            {error && (
              <div className="flex items-start gap-2 bg-red-50 border border-red-200 rounded-xl p-3">
                <AlertCircle className="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" />
                <p className="text-sm text-red-700">{error}</p>
              </div>
            )}

            <button
              type="submit"
              disabled={isLoading}
              className="w-full h-14 bg-gradient-to-r from-[#0057A8] to-[#00AEEF] text-white rounded-2xl font-black text-base shadow-lg shadow-[#0057A8]/30 active:scale-95 transition-all duration-200 flex items-center justify-center gap-2 disabled:opacity-70"
            >
              {isLoading ? (
                <>
                  <Loader2 className="w-5 h-5 animate-spin" />
                  Searching...
                </>
              ) : (
                <>
                  <Search className="w-5 h-5" />
                  Find My Bill
                </>
              )}
            </button>
          </form>

          {/* What you can do */}
          <div className="mt-6 pt-5 border-t border-gray-100">
            <p className="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">What you can do</p>
            <div className="space-y-2.5">
              {[
                { icon: User, label: "View customer statement" },
                { icon: Tag, label: "Filter by month & year" },
                { icon: MapPin, label: "Pay bills & get control numbers" },
              ].map(({ icon: Icon, label }) => (
                <div key={label} className="flex items-center gap-2.5">
                  <div className="w-7 h-7 rounded-lg bg-[#E8F1FB] flex items-center justify-center flex-shrink-0">
                    <Icon className="w-3.5 h-3.5 text-[#0057A8]" />
                  </div>
                  <span className="text-sm text-gray-600">{label}</span>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Footer note */}
        <p className="text-white/40 text-xs mt-8 text-center">
          Your data is not stored on this device.{"\n"}Session is cleared when you exit.
        </p>
      </div>
    </div>
  );
}
