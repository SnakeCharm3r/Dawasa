import { useEffect, useState } from "react";
import { useNavigate } from "react-router";
import { Card, CardContent } from "../components/ui/card";
import { Droplet, User, Hash, DollarSign, TrendingUp, Bell, LogOut, ChevronRight } from "lucide-react";
import { Badge } from "../components/ui/badge";
import { Button } from "../components/ui/button";
import { auth, bills as billsApi } from "../../utils/api";
import { toast } from "sonner";

export default function Dashboard() {
  const navigate = useNavigate();
  const [user, setUser] = useState<any>(null);
  const [bills, setBills] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      setIsLoading(true);
      const storedUser = auth.getStoredUser();
      setUser(storedUser);

      // Load bills from Supabase
      const userBills = await billsApi.getAll();
      setBills(userBills);
    } catch (error: any) {
      console.error("Error loading data:", error);
      if (error.message === "Not authenticated") {
        navigate("/");
      } else {
        toast.error("Failed to load dashboard data");
      }
    } finally {
      setIsLoading(false);
    }
  };

  const handleLogout = () => {
    auth.logout();
    navigate("/");
    toast.success("Logged out successfully");
  };

  const currentBill = bills.find(b => b.status === "Pending") || bills[0];
  const recentBills = bills.slice(0, 3);

  // Calculate totals
  const totalPaid = bills.filter(b => b.status === "Paid").reduce((sum, b) => sum + b.total, 0);
  const pendingAmount = bills.filter(b => b.status === "Pending").reduce((sum, b) => sum + b.total, 0);

  if (isLoading) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-900 via-blue-800 to-cyan-900 flex items-center justify-center">
        <div className="flex flex-col items-center gap-4">
          <div className="w-16 h-16 border-4 border-white/30 border-t-white rounded-full animate-spin"></div>
          <p className="text-white text-lg font-medium">Loading...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-900 via-blue-800 to-cyan-900">
      {/* Status Bar Spacer */}
      <div className="h-6 bg-transparent"></div>
      
      {/* Welcome Card */}
      <div className="bg-gradient-to-b from-blue-800/50 to-transparent px-6 py-8 mb-6">
        <div className="flex justify-between items-start mb-6">
          <div className="text-white">
            <p className="text-blue-200 text-base mb-2">Welcome back,</p>
            <h2 className="text-4xl font-black">
              {user?.firstName} {user?.lastName}
            </h2>
          </div>
          <Button
            onClick={handleLogout}
            variant="ghost"
            size="sm"
            className="text-white/80 hover:bg-white/20 hover:text-white rounded-2xl p-3"
          >
            <LogOut className="w-6 h-6" />
          </Button>
        </div>
        
        {currentBill && (
          <div className="bg-white/10 backdrop-blur-md rounded-3xl p-6 border border-white/20 shadow-2xl">
            <p className="text-blue-200 text-base mb-3 font-medium">Current Bill</p>
            <div className="flex justify-between items-end">
              <div>
                <p className="text-4xl font-black text-white">
                  {currentBill.total?.toLocaleString() || "0"} TZS
                </p>
                <p className="text-blue-200 text-base mt-2">Due: {currentBill.dueDate}</p>
              </div>
              <Button
                onClick={() => navigate("/payment")}
                className="bg-white text-blue-900 hover:bg-blue-50 rounded-2xl font-black px-8 py-4 text-lg shadow-2xl transition-all duration-300 hover:scale-105 active:scale-95"
              >
                Pay Now
              </Button>
            </div>
          </div>
        )}
      </div>

      {/* Quick Stats */}
      <div className="px-6 mb-6">
        <div className="grid grid-cols-2 gap-4">
          <div className="bg-white/10 backdrop-blur-md rounded-3xl p-5 border border-white/20 shadow-xl">
            <div className="flex items-center gap-4">
              <div className="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center border border-white/30">
                <Hash className="w-7 h-7 text-white" />
              </div>
              <div>
                <p className="text-white/80 text-sm font-medium">Customer ID</p>
                <p className="text-white text-lg font-bold truncate">
                  {user?.ipNumber || "N/A"}
                </p>
              </div>
            </div>
          </div>

          <div className="bg-white/10 backdrop-blur-md rounded-3xl p-5 border border-white/20 shadow-xl">
            <div className="flex items-center gap-4">
              <div className="w-14 h-14 rounded-2xl bg-green-500/20 backdrop-blur-sm flex items-center justify-center border border-green-400/30">
                <DollarSign className="w-7 h-7 text-green-300" />
              </div>
              <div>
                <p className="text-white/80 text-sm font-medium">Total Paid</p>
                <p className="text-white text-lg font-bold">
                  {totalPaid.toLocaleString()} TZS
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Quick Actions */}
      <div className="px-6 mb-6">
        <h3 className="text-2xl font-black text-white mb-4">Quick Actions</h3>
        <div className="space-y-3">
          <button
            onClick={() => navigate("/payment")}
            className="w-full bg-white/10 backdrop-blur-md rounded-3xl shadow-xl p-5 flex items-center justify-between hover:bg-white/20 transition-all duration-300 active:scale-98 border border-white/20"
          >
            <div className="flex items-center gap-4">
              <div className="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shadow-lg">
                <DollarSign className="w-7 h-7 text-white" />
              </div>
              <div className="text-left">
                <p className="font-bold text-white text-lg">Make Payment</p>
                <p className="text-white/80 text-sm">Pay your water bill</p>
              </div>
            </div>
            <ChevronRight className="w-6 h-6 text-white/60" />
          </button>

          <button
            onClick={() => navigate("/bills")}
            className="w-full bg-white/10 backdrop-blur-md rounded-3xl shadow-xl p-5 flex items-center justify-between hover:bg-white/20 transition-all duration-300 active:scale-98 border border-white/20"
          >
            <div className="flex items-center gap-4">
              <div className="w-14 h-14 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-500 flex items-center justify-center shadow-lg">
                <TrendingUp className="w-7 h-7 text-white" />
              </div>
              <div className="text-left">
                <p className="font-bold text-white text-lg">View Bills</p>
                <p className="text-white/80 text-sm">Check billing history</p>
              </div>
            </div>
            <ChevronRight className="w-6 h-6 text-white/60" />
          </button>

          <button
            onClick={() => navigate("/complaints")}
            className="w-full bg-white/10 backdrop-blur-md rounded-3xl shadow-xl p-5 flex items-center justify-between hover:bg-white/20 transition-all duration-300 active:scale-98 border border-white/20"
          >
            <div className="flex items-center gap-4">
              <div className="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center shadow-lg">
                <Bell className="w-7 h-7 text-white" />
              </div>
              <div className="text-left">
                <p className="font-bold text-white text-lg">Submit Complaint</p>
                <p className="text-white/80 text-sm">Report technical issues</p>
              </div>
            </div>
            <ChevronRight className="w-6 h-6 text-white/60" />
          </button>
        </div>
      </div>

      {/* Recent Bills */}
      {recentBills.length > 0 && (
        <div className="px-6 mb-6">
          <div className="flex justify-between items-center mb-4">
            <h3 className="text-2xl font-black text-white">Recent Bills</h3>
            <button
              onClick={() => navigate("/bills")}
              className="text-white/80 text-base font-bold hover:text-white transition-colors"
            >
              View All
            </button>
          </div>
          <div className="space-y-3">
            {recentBills.map((bill, index) => (
              <div
                key={index}
                className="bg-white/10 backdrop-blur-md rounded-3xl shadow-xl p-5 border border-white/20"
              >
                <div className="flex justify-between items-start mb-4">
                  <div>
                    <p className="font-bold text-white text-lg">{bill.month}</p>
                    <p className="text-white/80 text-sm">{bill.billingPeriod}</p>
                  </div>
                  <Badge
                    className={`${
                      bill.status === "Paid"
                        ? "bg-green-500/20 text-green-200 border-green-400/30"
                        : "bg-yellow-500/20 text-yellow-200 border-yellow-400/30"
                    }`}
                  >
                    {bill.status}
                  </Badge>
                </div>
                <div className="flex justify-between items-center">
                  <div>
                    <p className="text-white/80 text-sm">Amount</p>
                    <p className="text-xl font-bold text-white">
                      {bill.total?.toLocaleString() || "0"} TZS
                    </p>
                  </div>
                  <div className="text-right">
                    <p className="text-white/80 text-sm">Consumption</p>
                    <p className="text-xl font-bold text-blue-300">
                      {bill.consumption || "0"} m³
                    </p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* No Bills Message */}
      {bills.length === 0 && (
        <div className="px-4">
          <Card className="border-none shadow-md">
            <CardContent className="py-12 text-center">
              <Droplet className="w-16 h-16 text-gray-300 mx-auto mb-4" />
              <p className="text-gray-600 mb-2">No bills available yet</p>
              <p className="text-sm text-gray-500">
                Your bills will appear here once generated
              </p>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Account Info */}
      <div className="px-4 mb-6">
        <h3 className="text-lg font-bold text-gray-900 mb-3">Account Information</h3>
        <Card className="border-none shadow-md">
          <CardContent className="p-4 space-y-3">
            <div className="flex justify-between items-center py-2 border-b border-gray-100">
              <span className="text-sm text-gray-600">Account Holder</span>
              <span className="text-sm font-semibold text-gray-900">
                {user?.firstName} {user?.middleName} {user?.lastName}
              </span>
            </div>
            <div className="flex justify-between items-center py-2 border-b border-gray-100">
              <span className="text-sm text-gray-600">Location</span>
              <span className="text-sm font-semibold text-gray-900">{user?.location}</span>
            </div>
            <div className="flex justify-between items-center py-2">
              <span className="text-sm text-gray-600">Meter IP</span>
              <span className="text-sm font-mono font-semibold text-gray-900">
                {user?.ipNumber}
              </span>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
