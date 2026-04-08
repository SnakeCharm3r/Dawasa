import { useState, useEffect } from "react";
import { useNavigate } from "react-router";
import { Card, CardContent } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Input } from "../components/ui/input";
import { Label } from "../components/ui/label";
import { Badge } from "../components/ui/badge";
import { CreditCard, Check, Copy, AlertCircle, Smartphone, Building2, Users, Phone } from "lucide-react";
import { bills as billsApi, payments as paymentsApi } from "../../utils/supabase";
import { toast } from "sonner";

export default function Payment() {
  const navigate = useNavigate();
  const [bills, setBills] = useState<any[]>([]);
  const [paymentMethod, setPaymentMethod] = useState("mobile");
  const [amount, setAmount] = useState("");
  const [phoneNumber, setPhoneNumber] = useState("");
  const [controlNumber, setControlNumber] = useState("");
  const [showControlNumber, setShowControlNumber] = useState(false);
  const [isGenerating, setIsGenerating] = useState(false);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    loadBills();
  }, []);

  const loadBills = async () => {
    try {
      setIsLoading(true);
      const userBills = await billsApi.getAll();
      setBills(userBills);
      
      // Set amount to pending bill if exists
      const pendingBill = userBills.find(b => b.status === "Pending");
      if (pendingBill) {
        setAmount(pendingBill.total.toString());
      }
    } catch (error: any) {
      console.error("Error loading bills:", error);
      if (error.message === "Not authenticated") {
        navigate("/");
      }
    } finally {
      setIsLoading(false);
    }
  };

  const generateControlNumber = async () => {
    if (!amount || parseFloat(amount) <= 0) {
      toast.error("Please enter a valid amount");
      return;
    }

    if (paymentMethod === "mobile" && !phoneNumber) {
      toast.error("Please enter your phone number");
      return;
    }

    setIsGenerating(true);
    try {
      const pendingBill = bills.find(b => b.status === "Pending");
      const result = await paymentsApi.generateControlNumber({
        amount: parseFloat(amount),
        billId: pendingBill?.id,
        paymentMethod,
      });

      setControlNumber(result.controlNumber);
      setShowControlNumber(true);
      toast.success("Control number generated successfully!");
    } catch (error: any) {
      console.error("Error generating control number:", error);
      toast.error(error.message || "Failed to generate control number");
    } finally {
      setIsGenerating(false);
    }
  };

  const handleCopyControlNumber = () => {
    navigator.clipboard.writeText(controlNumber);
    toast.success("Control number copied!");
  };

  const paymentMethods = [
    { value: "mobile", label: "Mobile Money", icon: Smartphone },
    { value: "bank", label: "Bank", icon: Building2 },
    { value: "agent", label: "Agent", icon: Users },
  ];

  const currentBill = bills.find(b => b.status === "Pending");

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="flex flex-col items-center gap-3">
          <div className="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
          <p className="text-gray-600">Loading...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 pb-6">
      {/* Current Bill */}
      {currentBill && (
        <div className="px-4 py-4">
          <Card className="border-none shadow-lg bg-gradient-to-r from-blue-500 to-cyan-500 text-white">
            <CardContent className="pt-4 pb-4">
              <p className="text-blue-100 text-sm mb-2">Current Bill</p>
              <div className="flex justify-between items-end">
                <div>
                  <p className="text-3xl font-bold mb-1">
                    {currentBill.total?.toLocaleString() || "0"} TZS
                  </p>
                  <p className="text-blue-100 text-sm">{currentBill.month}</p>
                </div>
                <Badge className="bg-white/20 text-white border-white/30">
                  Due: {currentBill.dueDate}
                </Badge>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Payment Method Selection */}
      <div className="px-4 mb-4">
        <h3 className="text-sm font-semibold text-gray-700 mb-3">Payment Method</h3>
        <div className="grid grid-cols-3 gap-2">
          {paymentMethods.map((method) => (
            <button
              key={method.value}
              onClick={() => setPaymentMethod(method.value)}
              className={`p-4 rounded-2xl border-2 transition-all ${
                paymentMethod === method.value
                  ? "border-blue-500 bg-blue-50 shadow-md"
                  : "border-gray-200 bg-white"
              }`}
            >
              <div className={`w-10 h-10 rounded-xl mx-auto mb-2 flex items-center justify-center ${
                paymentMethod === method.value
                  ? "bg-gradient-to-br from-blue-500 to-cyan-500"
                  : "bg-gray-100"
              }`}>
                <method.icon className={`w-5 h-5 ${
                  paymentMethod === method.value ? "text-white" : "text-gray-600"
                }`} />
              </div>
              <p className={`text-xs font-semibold text-center ${
                paymentMethod === method.value ? "text-blue-900" : "text-gray-600"
              }`}>
                {method.label}
              </p>
            </button>
          ))}
        </div>
      </div>

      {/* Payment Form */}
      <div className="px-4 mb-4">
        <Card className="border-none shadow-md">
          <CardContent className="pt-6 pb-6 space-y-4">
            <div className="space-y-2">
              <Label htmlFor="amount" className="text-gray-700 text-sm">
                Amount (TZS)
              </Label>
              <Input
                id="amount"
                type="number"
                placeholder="Enter amount"
                value={amount}
                onChange={(e) => setAmount(e.target.value)}
                className="h-14 rounded-xl text-lg font-semibold border-gray-200"
              />
            </div>

            {paymentMethod === "mobile" && (
              <div className="space-y-2">
                <Label htmlFor="phone" className="text-gray-700 text-sm">
                  Mobile Number
                </Label>
                <Input
                  id="phone"
                  type="tel"
                  placeholder="+255 712 345 678"
                  value={phoneNumber}
                  onChange={(e) => setPhoneNumber(e.target.value)}
                  className="h-14 rounded-xl text-base border-gray-200"
                />
              </div>
            )}

            <Button
              onClick={generateControlNumber}
              disabled={isGenerating}
              className="w-full h-14 bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white rounded-xl shadow-lg text-base font-semibold"
            >
              {isGenerating ? (
                <div className="flex items-center gap-2">
                  <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                  Generating...
                </div>
              ) : (
                "Generate Control Number"
              )}
            </Button>
          </CardContent>
        </Card>
      </div>

      {/* Control Number Display */}
      {showControlNumber && (
        <div className="px-4 mb-4">
          <Card className="border-none shadow-xl bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-200">
            <CardContent className="pt-6 pb-6 space-y-4">
              <div className="flex items-center gap-3 mb-4">
                <div className="w-12 h-12 rounded-xl bg-green-500 flex items-center justify-center">
                  <Check className="w-6 h-6 text-white" />
                </div>
                <div>
                  <p className="font-bold text-gray-900">Success!</p>
                  <p className="text-sm text-gray-600">Control number generated</p>
                </div>
              </div>

              <div className="bg-white rounded-2xl p-5 border-2 border-green-300">
                <p className="text-xs text-gray-600 mb-3">Your Control Number</p>
                <div className="flex items-center justify-between mb-3">
                  <span className="text-2xl font-mono font-bold text-gray-900 tracking-wider">
                    {controlNumber}
                  </span>
                  <Button
                    onClick={handleCopyControlNumber}
                    variant="ghost"
                    size="sm"
                    className="text-blue-600 hover:bg-blue-50 rounded-lg"
                  >
                    <Copy className="w-5 h-5" />
                  </Button>
                </div>
                <p className="text-xs text-gray-500">
                  Valid for 48 hours
                </p>
              </div>

              <div className="bg-white rounded-2xl p-4 border border-green-200">
                <p className="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                  <Phone className="w-4 h-4 text-blue-500" />
                  How to Pay
                </p>
                <ol className="list-decimal list-inside space-y-2 text-sm text-gray-700">
                  <li>Dial *150*00# (M-Pesa) or your provider USSD</li>
                  <li>Select "Pay by Control Number"</li>
                  <li>Enter: <span className="font-mono font-semibold">{controlNumber}</span></li>
                  <li>Confirm amount: <span className="font-semibold">{amount} TZS</span></li>
                  <li>Enter PIN to complete</li>
                </ol>
              </div>

              <div className="bg-yellow-50 border-2 border-yellow-200 rounded-2xl p-4">
                <div className="flex items-start gap-3">
                  <AlertCircle className="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" />
                  <div className="text-sm text-gray-700">
                    <p className="font-semibold text-gray-900 mb-1">Important</p>
                    <p>Payment reflects within 24 hours. Keep this control number safe.</p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Payment Info */}
      <div className="px-4">
        <Card className="border-none shadow-md">
          <CardContent className="pt-5 pb-5">
            <h3 className="font-semibold text-gray-900 mb-3">Payment Methods Accepted</h3>
            <div className="space-y-2 text-sm">
              <div className="flex items-center gap-2 text-gray-700">
                <Smartphone className="w-4 h-4 text-blue-500" />
                <span>M-Pesa, Tigo Pesa, Airtel Money</span>
              </div>
              <div className="flex items-center gap-2 text-gray-700">
                <Building2 className="w-4 h-4 text-blue-500" />
                <span>Bank Transfer (CRDB, NMB, NBC)</span>
              </div>
              <div className="flex items-center gap-2 text-gray-700">
                <Users className="w-4 h-4 text-blue-500" />
                <span>DAWASA Service Centers</span>
              </div>
            </div>

            <div className="mt-4 pt-4 border-t border-gray-200">
              <p className="text-xs text-gray-600 mb-2">Need Help?</p>
              <p className="text-sm font-semibold text-blue-600">+255 22 245 3511</p>
              <p className="text-xs text-gray-500">Mon-Fri: 8AM - 5PM</p>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
