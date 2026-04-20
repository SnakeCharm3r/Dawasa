import { Outlet, NavLink, useNavigate, useLocation } from "react-router";
import { Droplet, FileText, CreditCard, MessageSquare, Receipt, RefreshCw } from "lucide-react";
import { useEffect, useState } from "react";
import { useCapacitor } from "../../utils/capacitor";
import { session, type CustomerInfo } from "../../utils/supabase";

const navItems = [
  { to: "/statement", icon: FileText, label: "Bills" },
  { to: "/payment", icon: CreditCard, label: "Pay" },
  { to: "/complaints", icon: MessageSquare, label: "Support" },
  { to: "/receipts", icon: Receipt, label: "Receipts" },
];

export default function MobileLayout() {
  const navigate = useNavigate();
  const location = useLocation();
  const [customer, setCustomer] = useState<CustomerInfo | null>(null);

  useEffect(() => {
    const info = session.getCustomer();
    if (!info) {
      navigate("/", { replace: true });
      return;
    }
    setCustomer(info);
  }, [navigate]);

  const getPageTitle = () => {
    const path = location.pathname;
    if (path === "/statement") return "Bill Statement";
    if (path === "/payment") return "Payment";
    if (path === "/complaints") return "Support";
    if (path === "/receipts") return "Receipts";
    return "DAWASA";
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-[#003F7F] via-[#0057A8] to-[#005f8e] flex flex-col pb-24">
      {/* Status Bar Spacer */}
      <div className="h-6 bg-transparent"></div>
      
      {/* Mobile Header */}
      <header className="bg-gradient-to-b from-[#003F7F]/60 to-transparent text-white sticky top-6 z-50 backdrop-blur-md">
        <div className="px-5 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <div className="bg-white/10 backdrop-blur-md p-2.5 rounded-xl border border-white/20 shadow-xl">
                <Droplet className="w-6 h-6 text-white" fill="white" />
              </div>
              <div>
                <h1 className="text-lg font-black leading-tight">{getPageTitle()}</h1>
                {customer && (
                  <p className="text-xs text-[#00AEEF] font-medium truncate max-w-[180px]">
                    {customer.meterNumber} · {customer.customerName.split(' ')[0]}
                  </p>
                )}
              </div>
            </div>
            <button
              onClick={() => { session.clear(); navigate("/", { replace: true }); }}
              className="bg-white/10 border border-white/20 rounded-xl px-3 py-2 flex items-center gap-1.5 text-white/80 text-xs font-semibold"
            >
              <RefreshCw className="w-3.5 h-3.5" />
              Change
            </button>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="flex-1 overflow-y-auto">
        <Outlet />
      </main>

      {/* Bottom Navigation */}
      <nav className="fixed bottom-0 left-0 right-0 bg-white/10 backdrop-blur-md border-t border-white/20 shadow-2xl z-50">
        <div className="grid grid-cols-4 h-24">
          {navItems.map((item) => {
            const isActive = location.pathname === item.to;
            return (
              <NavLink
                key={item.to}
                to={item.to}
                className="flex flex-col items-center justify-center gap-2 transition-all duration-300"
              >
                <div
                  className={`p-3 rounded-2xl transition-all duration-300 ${
                    isActive
                      ? "bg-white text-[#0057A8] shadow-2xl scale-110"
                      : "bg-white/10 backdrop-blur-sm border border-white/20"
                  }`}
                >
                  <item.icon
                    className={`w-6 h-6 transition-all duration-300 ${
                      isActive ? "text-[#0057A8]" : "text-white/80"
                    }`}
                  />
                </div>
                <span
                  className={`text-xs font-bold transition-all duration-300 ${
                    isActive ? "text-white" : "text-white/60"
                  }`}
                >
                  {item.label}
                </span>
              </NavLink>
            );
          })}
        </div>
      </nav>
    </div>
  );
}
