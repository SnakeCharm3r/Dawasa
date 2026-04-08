import { Outlet, NavLink, useNavigate, useLocation } from "react-router";
import { Home, FileText, CreditCard, MessageSquare, Droplet } from "lucide-react";
import { useEffect, useState } from "react";
import { auth } from "../../utils/api";

export default function MobileLayout() {
  const navigate = useNavigate();
  const location = useLocation();
  const [user, setUser] = useState<any>(null);

  useEffect(() => {
    const storedUser = auth.getStoredUser();
    setUser(storedUser);
  }, []);

  const navItems = [
    { to: "/dashboard", icon: Home, label: "Home" },
    { to: "/bills", icon: FileText, label: "Bills" },
    { to: "/payment", icon: CreditCard, label: "Pay" },
    { to: "/complaints", icon: MessageSquare, label: "Support" },
  ];

  const getPageTitle = () => {
    const path = location.pathname;
    if (path === "/dashboard") return "Dashboard";
    if (path === "/bills") return "Bills";
    if (path === "/payment") return "Payment";
    if (path === "/complaints") return "Support";
    return "DAWASA";
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-900 via-blue-800 to-cyan-900 flex flex-col pb-24">
      {/* Status Bar Spacer */}
      <div className="h-6 bg-transparent"></div>
      
      {/* Mobile Header */}
      <header className="bg-gradient-to-b from-blue-800/50 to-transparent text-white sticky top-6 z-50 backdrop-blur-md">
        <div className="px-6 py-6">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              <div className="bg-white/10 backdrop-blur-md p-3 rounded-2xl border border-white/20 shadow-xl">
                <Droplet className="w-7 h-7 text-white" fill="white" />
              </div>
              <div>
                <h1 className="text-2xl font-black">{getPageTitle()}</h1>
                {user && (
                  <p className="text-sm text-blue-200 font-medium">
                    {user.firstName} {user.lastName}
                  </p>
                )}
              </div>
            </div>
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
                      ? "bg-white text-blue-900 shadow-2xl scale-110"
                      : "bg-white/10 backdrop-blur-sm border border-white/20"
                  }`}
                >
                  <item.icon
                    className={`w-6 h-6 transition-all duration-300 ${
                      isActive ? "text-blue-900" : "text-white/80"
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
