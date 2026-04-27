import { useState, useEffect } from "react";
import { useNavigate } from "react-router";
import { Card, CardContent } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Input } from "../components/ui/input";
import { Label } from "../components/ui/label";
import { Textarea } from "../components/ui/textarea";
import { Badge } from "../components/ui/badge";
import { MessageSquare, Send, Clock, CheckCircle, AlertCircle, ChevronDown, ChevronUp, Plus } from "lucide-react";
import { complaints as complaintsApi } from "../../utils/supabase";
import { toast } from "sonner";

export default function Complaints() {
  const navigate = useNavigate();
  const [complaints, setComplaints] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [expandedComplaint, setExpandedComplaint] = useState<string | null>(null);

  // Form fields
  const [complaintType, setComplaintType] = useState("");
  const [description, setDescription] = useState("");
  const [location, setLocation] = useState("");
  const [urgency, setUrgency] = useState("medium");

  useEffect(() => {
    loadComplaints();
  }, []);

  const loadComplaints = async () => {
    try {
      setIsLoading(true);
      const userComplaints = await complaintsApi.getAll();
      setComplaints(userComplaints);
    } catch (error: any) {
      console.error("Error loading complaints:", error);
      if (error.message === "Not authenticated") {
        navigate("/");
      } else {
        toast.error("Failed to load complaints");
      }
    } finally {
      setIsLoading(false);
    }
  };

  const handleSubmitComplaint = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!complaintType || !description) {
      toast.error("Please fill in all required fields");
      return;
    }

    setIsSubmitting(true);
    try {
      const newComplaint = await complaintsApi.submit({
        type: complaintType,
        description,
        location,
        urgency,
        contactMethod: "phone",
      });

      toast.success(`Complaint submitted! Reference: ${newComplaint.id}`);
      
      // Reset form
      setComplaintType("");
      setDescription("");
      setLocation("");
      setUrgency("medium");
      setShowForm(false);

      // Reload complaints
      await loadComplaints();
    } catch (error: any) {
      console.error("Error submitting complaint:", error);
      toast.error(error.message || "Failed to submit complaint");
    } finally {
      setIsSubmitting(false);
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case "Resolved":
        return <CheckCircle className="w-4 h-4 text-green-500" />;
      case "In Progress":
        return <Clock className="w-4 h-4 text-blue-500" />;
      case "Pending":
        return <AlertCircle className="w-4 h-4 text-yellow-500" />;
      default:
        return null;
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case "Resolved":
        return "bg-green-100 text-green-700 border-green-200";
      case "In Progress":
        return "bg-blue-100 text-blue-700 border-blue-200";
      case "Pending":
        return "bg-yellow-100 text-yellow-700 border-yellow-200";
      default:
        return "bg-gray-100 text-gray-700 border-gray-200";
    }
  };

  const complaintTypes = [
    "Water Leakage",
    "Water Quality Issues",
    "Low Water Pressure",
    "Meter Reading Issues",
    "Billing Discrepancy",
    "Water Supply Interruption",
    "Other Technical Issues",
  ];

  const urgencyLevels = [
    { value: "low", label: "Low", color: "text-gray-700" },
    { value: "medium", label: "Medium", color: "text-yellow-700" },
    { value: "high", label: "High", color: "text-orange-700" },
    { value: "critical", label: "Critical", color: "text-red-700" },
  ];

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

  const resolvedCount = complaints.filter(c => c.status === "Resolved").length;
  const pendingCount = complaints.filter(c => c.status === "Pending").length;
  const inProgressCount = complaints.filter(c => c.status === "In Progress").length;

  return (
    <div className="min-h-screen bg-gray-50 pb-6">
      {/* Stats */}
      <div className="px-4 py-4">
        <div className="grid grid-cols-3 gap-3 mb-4">
          <Card className="border-none shadow-md bg-gradient-to-br from-green-50 to-emerald-50">
            <CardContent className="pt-4 pb-4 text-center">
              <p className="text-2xl font-bold text-green-600">{resolvedCount}</p>
              <p className="text-xs text-gray-600 mt-1">Resolved</p>
            </CardContent>
          </Card>
          <Card className="border-none shadow-md bg-gradient-to-br from-blue-50 to-cyan-50">
            <CardContent className="pt-4 pb-4 text-center">
              <p className="text-2xl font-bold text-blue-600">{inProgressCount}</p>
              <p className="text-xs text-gray-600 mt-1">In Progress</p>
            </CardContent>
          </Card>
          <Card className="border-none shadow-md bg-gradient-to-br from-yellow-50 to-orange-50">
            <CardContent className="pt-4 pb-4 text-center">
              <p className="text-2xl font-bold text-orange-600">{pendingCount}</p>
              <p className="text-xs text-gray-600 mt-1">Pending</p>
            </CardContent>
          </Card>
        </div>
      </div>

      {/* Submit Button */}
      {!showForm && (
        <div className="px-4 mb-4">
          <Button
            onClick={() => setShowForm(true)}
            className="w-full h-14 bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white rounded-xl shadow-lg text-base font-semibold"
          >
            <Plus className="w-5 h-5 mr-2" />
            Submit New Complaint
          </Button>
        </div>
      )}

      {/* Complaint Form */}
      {showForm && (
        <div className="px-4 mb-4">
          <Card className="border-none shadow-lg">
            <CardContent className="pt-6 pb-6">
              <div className="flex items-center justify-between mb-6">
                <h3 className="text-lg font-bold text-gray-900">New Complaint</h3>
                <button
                  onClick={() => setShowForm(false)}
                  className="text-gray-500 hover:text-gray-700"
                >
                  ✕
                </button>
              </div>

              <form onSubmit={handleSubmitComplaint} className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="type" className="text-gray-700 text-sm">
                    Complaint Type <span className="text-red-500">*</span>
                  </Label>
                  <select
                    id="type"
                    value={complaintType}
                    onChange={(e) => setComplaintType(e.target.value)}
                    className="w-full h-14 px-4 rounded-xl border border-gray-200 text-base bg-white"
                    required
                  >
                    <option value="">Select type</option>
                    {complaintTypes.map((type) => (
                      <option key={type} value={type}>
                        {type}
                      </option>
                    ))}
                  </select>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="description" className="text-gray-700 text-sm">
                    Description <span className="text-red-500">*</span>
                  </Label>
                  <Textarea
                    id="description"
                    placeholder="Describe the issue in detail..."
                    value={description}
                    onChange={(e) => setDescription(e.target.value)}
                    className="min-h-32 rounded-xl border-gray-200 resize-none text-base"
                    required
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="location" className="text-gray-700 text-sm">
                    Specific Location
                  </Label>
                  <Input
                    id="location"
                    type="text"
                    placeholder="e.g., Near main gate"
                    value={location}
                    onChange={(e) => setLocation(e.target.value)}
                    className="h-14 rounded-xl border-gray-200 text-base"
                  />
                </div>

                <div className="space-y-2">
                  <Label className="text-gray-700 text-sm">Urgency Level</Label>
                  <div className="grid grid-cols-2 gap-2">
                    {urgencyLevels.map((level) => (
                      <button
                        key={level.value}
                        type="button"
                        onClick={() => setUrgency(level.value)}
                        className={`py-3 px-4 rounded-xl border-2 text-sm font-semibold transition-all ${
                          urgency === level.value
                            ? "border-blue-500 bg-blue-50 text-blue-700"
                            : "border-gray-200 bg-white text-gray-700"
                        }`}
                      >
                        {level.label}
                      </button>
                    ))}
                  </div>
                </div>

                <div className="flex gap-2 pt-2">
                  <Button
                    type="button"
                    onClick={() => setShowForm(false)}
                    variant="outline"
                    className="flex-1 h-12 rounded-xl border-gray-300"
                  >
                    Cancel
                  </Button>
                  <Button
                    type="submit"
                    disabled={isSubmitting}
                    className="flex-1 h-12 bg-gradient-to-r from-blue-500 to-cyan-500 text-white rounded-xl"
                  >
                    {isSubmitting ? (
                      <div className="flex items-center gap-2">
                        <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                        Submitting...
                      </div>
                    ) : (
                      <>
                        <Send className="w-4 h-4 mr-2" />
                        Submit
                      </>
                    )}
                  </Button>
                </div>
              </form>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Complaints List */}
      <div className="px-4">
        {complaints.length === 0 ? (
          <Card className="border-none shadow-md">
            <CardContent className="py-12 text-center">
              <MessageSquare className="w-16 h-16 text-gray-300 mx-auto mb-4" />
              <p className="text-gray-600 mb-2">No complaints yet</p>
              <p className="text-sm text-gray-500">
                Submit a complaint to get technical support
              </p>
            </CardContent>
          </Card>
        ) : (
          <div className="space-y-3">
            {complaints.map((complaint) => {
              const isExpanded = expandedComplaint === complaint.id;
              
              return (
                <Card key={complaint.id} className="border-none shadow-md">
                  <CardContent className="p-4">
                    <div className="flex items-start justify-between mb-3">
                      <div className="flex-1">
                        <div className="flex items-center gap-2 mb-2">
                          <Badge variant="outline" className="font-mono text-xs">
                            {complaint.id}
                          </Badge>
                          <span className="text-xs text-gray-500">
                            {new Date(complaint.createdAt).toLocaleDateString()}
                          </span>
                        </div>
                        <p className="font-bold text-gray-900 mb-1">{complaint.type}</p>
                        <p className="text-sm text-gray-600 line-clamp-2">
                          {complaint.description}
                        </p>
                      </div>
                      <Badge className={`${getStatusColor(complaint.status)} flex items-center gap-1 ml-2 whitespace-nowrap`}>
                        {getStatusIcon(complaint.status)}
                        {complaint.status}
                      </Badge>
                    </div>

                    <button
                      onClick={() => setExpandedComplaint(isExpanded ? null : complaint.id)}
                      className="w-full flex items-center justify-center gap-2 text-blue-600 font-semibold py-2 rounded-lg hover:bg-blue-50 transition-colors text-sm"
                    >
                      {isExpanded ? (
                        <>
                          <span>Hide Response</span>
                          <ChevronUp className="w-4 h-4" />
                        </>
                      ) : (
                        <>
                          <span>View Response</span>
                          <ChevronDown className="w-4 h-4" />
                        </>
                      )}
                    </button>

                    {isExpanded && (
                      <div className="mt-4 p-4 bg-blue-50 rounded-xl border-l-4 border-blue-500">
                        <p className="text-xs font-semibold text-gray-700 mb-2">
                          Technician Response:
                        </p>
                        <p className="text-sm text-gray-700">{complaint.response}</p>
                      </div>
                    )}
                  </CardContent>
                </Card>
              );
            })}
          </div>
        )}
      </div>

      {/* Emergency Contact */}
      <div className="px-4 mt-6">
        <Card className="border-none shadow-md bg-gradient-to-br from-red-50 to-orange-50 border-2 border-red-200">
          <CardContent className="pt-5 pb-5">
            <h3 className="font-bold text-gray-900 mb-2 flex items-center gap-2">
              <AlertCircle className="w-5 h-5 text-red-600" />
              Emergency Contact
            </h3>
            <p className="text-sm text-gray-600 mb-3">
              For urgent issues like major leaks or water contamination
            </p>
            <p className="text-2xl font-bold text-red-600">+255 22 245 3511</p>
            <p className="text-xs text-gray-500 mt-1">24/7 Emergency Hotline</p>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
