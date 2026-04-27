import { useState, useEffect } from "react";
import { useNavigate } from "react-router";
import { Card, CardContent } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Badge } from "../components/ui/badge";
import { Download, FileText, Calendar, ChevronDown, ChevronUp } from "lucide-react";
import { bills as billsApi } from "../../utils/supabase";
import { toast } from "sonner";

export default function Bills() {
  const navigate = useNavigate();
  const [bills, setBills] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [expandedBill, setExpandedBill] = useState<string | null>(null);

  useEffect(() => {
    loadBills();
  }, []);

  const loadBills = async () => {
    try {
      setIsLoading(true);
      const userBills = await billsApi.getAll();
      setBills(userBills);
    } catch (error: any) {
      console.error("Error loading bills:", error);
      if (error.message === "Not authenticated") {
        navigate("/");
      } else {
        toast.error("Failed to load bills");
      }
    } finally {
      setIsLoading(false);
    }
  };

  const handleExportBill = (bill: any) => {
    const exportData = `
DAWASA Water Services - Bill Export
===================================
Billing Period: ${bill.billingPeriod}
Due Date: ${bill.dueDate}

Meter Readings:
Previous: ${bill.meterReading?.previous || 0} m³
Current: ${bill.meterReading?.current || 0} m³
Consumption: ${bill.consumption} m³

Charges:
Base Charge: ${bill.baseCharge?.toLocaleString() || 0} TZS
Water Charge: ${bill.waterCharge?.toLocaleString() || 0} TZS
VAT: ${bill.vat?.toLocaleString() || 0} TZS
-----------------------------------
Total Amount: ${bill.total?.toLocaleString() || 0} TZS

Status: ${bill.status}
    `.trim();

    const blob = new Blob([exportData], { type: "text/plain" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `DAWASA_Bill_${bill.month?.replace(" ", "_")}.txt`;
    a.click();
    window.URL.revokeObjectURL(url);
    toast.success("Bill exported successfully!");
  };

  const toggleExpand = (billId: string) => {
    setExpandedBill(expandedBill === billId ? null : billId);
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="flex flex-col items-center gap-3">
          <div className="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
          <p className="text-gray-600">Loading bills...</p>
        </div>
      </div>
    );
  }

  const paidBills = bills.filter(b => b.status === "Paid").length;
  const pendingBills = bills.filter(b => b.status === "Pending").length;

  return (
    <div className="min-h-screen bg-gray-50 pb-6">
      {/* Summary Cards */}
      <div className="px-4 py-4">
        <div className="grid grid-cols-3 gap-3 mb-4">
          <Card className="border-none shadow-md bg-gradient-to-br from-blue-50 to-cyan-50">
            <CardContent className="pt-4 pb-4 text-center">
              <p className="text-2xl font-bold text-blue-600">{bills.length}</p>
              <p className="text-xs text-gray-600 mt-1">Total</p>
            </CardContent>
          </Card>
          <Card className="border-none shadow-md bg-gradient-to-br from-green-50 to-emerald-50">
            <CardContent className="pt-4 pb-4 text-center">
              <p className="text-2xl font-bold text-green-600">{paidBills}</p>
              <p className="text-xs text-gray-600 mt-1">Paid</p>
            </CardContent>
          </Card>
          <Card className="border-none shadow-md bg-gradient-to-br from-yellow-50 to-orange-50">
            <CardContent className="pt-4 pb-4 text-center">
              <p className="text-2xl font-bold text-orange-600">{pendingBills}</p>
              <p className="text-xs text-gray-600 mt-1">Pending</p>
            </CardContent>
          </Card>
        </div>
      </div>

      {/* Bills List */}
      <div className="px-4">
        {bills.length === 0 ? (
          <Card className="border-none shadow-md">
            <CardContent className="py-12 text-center">
              <FileText className="w-16 h-16 text-gray-300 mx-auto mb-4" />
              <p className="text-gray-600 mb-2">No bills available</p>
              <p className="text-sm text-gray-500">
                Your bills will appear here once generated
              </p>
            </CardContent>
          </Card>
        ) : (
          <div className="space-y-3">
            {bills.map((bill) => {
              const isExpanded = expandedBill === bill.id;
              
              return (
                <Card key={bill.id} className="border-none shadow-md overflow-hidden">
                  <CardContent className="p-0">
                    {/* Bill Header */}
                    <div className="p-4 bg-white">
                      <div className="flex justify-between items-start mb-3">
                        <div className="flex-1">
                          <h3 className="font-bold text-gray-900 text-lg mb-1">
                            {bill.month}
                          </h3>
                          <p className="text-xs text-gray-500">{bill.billingPeriod}</p>
                        </div>
                        <Badge
                          className={`${
                            bill.status === "Paid"
                              ? "bg-green-100 text-green-700 border-green-200"
                              : "bg-yellow-100 text-yellow-700 border-yellow-200"
                          }`}
                        >
                          {bill.status}
                        </Badge>
                      </div>

                      {/* Quick Info */}
                      <div className="grid grid-cols-2 gap-4 mb-4">
                        <div className="bg-blue-50 rounded-xl p-3">
                          <p className="text-xs text-gray-600 mb-1">Amount</p>
                          <p className="text-xl font-bold text-gray-900">
                            {bill.total?.toLocaleString() || "0"} <span className="text-sm">TZS</span>
                          </p>
                        </div>
                        <div className="bg-cyan-50 rounded-xl p-3">
                          <p className="text-xs text-gray-600 mb-1">Consumption</p>
                          <p className="text-xl font-bold text-blue-600">
                            {bill.consumption || "0"} <span className="text-sm">m³</span>
                          </p>
                        </div>
                      </div>

                      {/* Due Date */}
                      <div className="flex items-center gap-2 text-sm text-gray-600 mb-3">
                        <Calendar className="w-4 h-4" />
                        <span>Due: {bill.dueDate}</span>
                      </div>

                      {/* Expand Button */}
                      <button
                        onClick={() => toggleExpand(bill.id)}
                        className="w-full flex items-center justify-center gap-2 text-blue-600 font-semibold py-2 rounded-lg hover:bg-blue-50 transition-colors"
                      >
                        {isExpanded ? (
                          <>
                            <span>Hide Details</span>
                            <ChevronUp className="w-4 h-4" />
                          </>
                        ) : (
                          <>
                            <span>View Details</span>
                            <ChevronDown className="w-4 h-4" />
                          </>
                        )}
                      </button>
                    </div>

                    {/* Expanded Details */}
                    {isExpanded && (
                      <div className="bg-gray-50 border-t border-gray-100 p-4 space-y-4">
                        {/* Meter Readings */}
                        <div>
                          <p className="text-sm font-semibold text-gray-700 mb-3">
                            Meter Readings
                          </p>
                          <div className="grid grid-cols-2 gap-3">
                            <div className="bg-white rounded-xl p-3">
                              <p className="text-xs text-gray-500 mb-1">Previous</p>
                              <p className="text-lg font-bold text-gray-900">
                                {bill.meterReading?.previous || 0} m³
                              </p>
                            </div>
                            <div className="bg-white rounded-xl p-3">
                              <p className="text-xs text-gray-500 mb-1">Current</p>
                              <p className="text-lg font-bold text-gray-900">
                                {bill.meterReading?.current || 0} m³
                              </p>
                            </div>
                          </div>
                        </div>

                        {/* Charges Breakdown */}
                        <div>
                          <p className="text-sm font-semibold text-gray-700 mb-3">
                            Charges Breakdown
                          </p>
                          <div className="bg-white rounded-xl p-4 space-y-3">
                            <div className="flex justify-between text-sm">
                              <span className="text-gray-600">Base Charge</span>
                              <span className="font-semibold text-gray-900">
                                {bill.baseCharge?.toLocaleString() || 0} TZS
                              </span>
                            </div>
                            <div className="flex justify-between text-sm">
                              <span className="text-gray-600">Water Charge</span>
                              <span className="font-semibold text-gray-900">
                                {bill.waterCharge?.toLocaleString() || 0} TZS
                              </span>
                            </div>
                            <div className="flex justify-between text-sm pb-3 border-b border-gray-200">
                              <span className="text-gray-600">VAT</span>
                              <span className="font-semibold text-gray-900">
                                {bill.vat?.toLocaleString() || 0} TZS
                              </span>
                            </div>
                            <div className="flex justify-between pt-2">
                              <span className="font-bold text-gray-900">Total</span>
                              <span className="text-xl font-bold text-blue-600">
                                {bill.total?.toLocaleString() || 0} TZS
                              </span>
                            </div>
                          </div>
                        </div>

                        {/* Actions */}
                        <div className="flex gap-2">
                          <Button
                            onClick={() => handleExportBill(bill)}
                            variant="outline"
                            className="flex-1 h-12 rounded-xl border-blue-300 text-blue-600"
                          >
                            <Download className="w-4 h-4 mr-2" />
                            Export
                          </Button>
                          {bill.status === "Pending" && (
                            <Button
                              onClick={() => navigate("/payment")}
                              className="flex-1 h-12 rounded-xl bg-gradient-to-r from-blue-500 to-cyan-500 text-white"
                            >
                              Pay Now
                            </Button>
                          )}
                        </div>
                      </div>
                    )}
                  </CardContent>
                </Card>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}
