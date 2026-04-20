import { useState, useEffect, useCallback } from "react";
import { useNavigate } from "react-router";
import { MessageSquare, Send, Clock, CheckCircle2, AlertCircle, ChevronDown, ChevronUp, Plus, Droplets, Wrench, FileX, Zap, X, Loader2, Phone, MapPin, Navigation, ExternalLink, Map } from "lucide-react";
import { complaints, session, type Complaint, type ComplaintType } from "../../utils/supabase";
import { toast } from "sonner";
import MapPicker from "../components/MapPicker";

const COMPLAINT_CATEGORIES: { type: ComplaintType; icon: React.ElementType; color: string }[] = [
  { type: "Low Water Pressure / Low Flow",  icon: Droplets, color: "bg-blue-400/20 text-blue-300" },
  { type: "Water Leakage",                  icon: Droplets, color: "bg-[#00AEEF]/20 text-[#00AEEF]" },
  { type: "Pipe / Equipment Damage",        icon: Wrench,   color: "bg-orange-400/20 text-orange-300" },
  { type: "Wrong Bill / Billing Error",     icon: FileX,    color: "bg-red-400/20 text-red-300" },
  { type: "Water Quality Issue",            icon: Zap,      color: "bg-yellow-400/20 text-yellow-300" },
  { type: "No Water Supply",               icon: AlertCircle, color: "bg-red-500/20 text-red-400" },
  { type: "Meter Reading Dispute",          icon: FileX,    color: "bg-purple-400/20 text-purple-300" },
  { type: "Other",                          icon: MessageSquare, color: "bg-white/10 text-white/60" },
];

const URGENCY_LEVELS = [
  { value: "low"      as const, label: "Low",      dot: "bg-green-400" },
  { value: "medium"   as const, label: "Medium",   dot: "bg-yellow-400" },
  { value: "high"     as const, label: "High",     dot: "bg-orange-400" },
  { value: "critical" as const, label: "Critical", dot: "bg-red-500" },
];

export default function Complaints() {
  const navigate = useNavigate();
  const [meterNumber, setMeterNumber] = useState("");
  const [complaintList, setComplaintList] = useState<Complaint[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [expanded, setExpanded] = useState<string | null>(null);

  const [selectedType, setSelectedType] = useState<ComplaintType | "">("");
  const [description, setDescription] = useState("");
  const [location, setLocation] = useState("");
  const [locationCoords, setLocationCoords] = useState<{ lat: number; lng: number } | null>(null);
  const [isLocating, setIsLocating] = useState(false);
  const [showMap, setShowMap] = useState(false);
  const [urgency, setUrgency] = useState<"low" | "medium" | "high" | "critical">("medium");
  const [contactPhone, setContactPhone] = useState("");

  useEffect(() => {
    const mn = session.getMeter();
    if (!mn) { navigate("/", { replace: true }); return; }
    setMeterNumber(mn);
    loadComplaints(mn);
  }, [navigate]);

  const loadComplaints = async (mn: string) => {
    setIsLoading(true);
    try {
      const data = await complaints.getByMeter(mn);
      setComplaintList(data);
    } catch (err: any) {
      toast.error(err.message || "Failed to load complaints");
    } finally {
      setIsLoading(false);
    }
  };

  const handleGetLocation = useCallback(() => {
    if (!navigator.geolocation) {
      toast.error("Geolocation is not supported on this device.");
      return;
    }
    setIsLocating(true);
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const { latitude: lat, longitude: lng } = pos.coords;
        setLocationCoords({ lat, lng });
        setLocation(`${lat.toFixed(6)}, ${lng.toFixed(6)}`);
        setIsLocating(false);
        toast.success("Location pinned!");
      },
      (err) => {
        setIsLocating(false);
        toast.error(err.code === 1 ? "Location access denied. Please enter address manually." : "Could not get location. Try again.");
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedType || !description.trim()) {
      toast.error("Please select a complaint type and add a description.");
      return;
    }
    setIsSubmitting(true);
    try {
      const result = await complaints.submit({
        meterNumber,
        type: selectedType as ComplaintType,
        description,
        location,
        urgency,
        contactPhone,
      });
      toast.success(`Complaint submitted! Ref: ${result.id}`);
      setShowForm(false);
      setSelectedType("");
      setDescription("");
      setLocation("");
      setLocationCoords(null);
      setShowMap(false);
      setUrgency("medium");
      setContactPhone("");
      await loadComplaints(meterNumber);
    } catch (err: any) {
      toast.error(err.message || "Failed to submit complaint");
    } finally {
      setIsSubmitting(false);
    }
  };

  const statusStyle = (status: string) => {
    if (status === "Resolved")   return "bg-[#00A651]/20 text-[#00A651]";
    if (status === "In Progress") return "bg-[#00AEEF]/20 text-[#00AEEF]";
    return "bg-yellow-400/20 text-yellow-300";
  };

  const StatusIcon = ({ status }: { status: string }) => {
    if (status === "Resolved")    return <CheckCircle2 className="w-3.5 h-3.5" />;
    if (status === "In Progress") return <Clock className="w-3.5 h-3.5" />;
    return <AlertCircle className="w-3.5 h-3.5" />;
  };

  if (isLoading) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[60vh] gap-3">
        <Loader2 className="w-10 h-10 text-white animate-spin" />
        <p className="text-white/80 font-medium">Loading complaints…</p>
      </div>
    );
  }

  const resolved    = complaintList.filter(c => c.status === "Resolved").length;
  const inProgress  = complaintList.filter(c => c.status === "In Progress").length;
  const pending     = complaintList.filter(c => c.status === "Pending").length;

  return (
    <div className="pb-8 px-4 pt-4 space-y-4">

      {/* Full-screen map picker modal */}
      {showMap && (
        <MapPicker
          value={locationCoords}
          onChange={(coords, label) => {
            setLocationCoords(coords);
            setLocation(label);
          }}
          onClose={() => setShowMap(false)}
        />
      )}

      {/* Stats Row */}
      <div className="grid grid-cols-3 gap-2">
        {[
          { label: "Resolved",    count: resolved,   color: "text-[#00A651]" },
          { label: "In Progress", count: inProgress, color: "text-[#00AEEF]" },
          { label: "Pending",     count: pending,    color: "text-yellow-300" },
        ].map(({ label, count, color }) => (
          <div key={label} className="bg-white/10 border border-white/20 rounded-2xl p-3 text-center">
            <p className={`text-2xl font-black ${color}`}>{count}</p>
            <p className="text-white/50 text-[10px] mt-0.5">{label}</p>
          </div>
        ))}
      </div>

      {/* New Complaint Button */}
      {!showForm && (
        <button
          onClick={() => setShowForm(true)}
          className="w-full h-14 bg-white text-[#003F7F] rounded-2xl font-black flex items-center justify-center gap-2 shadow-lg active:scale-95 transition-all"
        >
          <Plus className="w-5 h-5" /> New Complaint
        </button>
      )}

      {/* Form */}
      {showForm && (
        <div className="bg-white rounded-3xl p-5 space-y-4 shadow-2xl">
          <div className="flex items-center justify-between">
            <h3 className="text-[#003F7F] font-black text-lg">New Complaint</h3>
            <button onClick={() => setShowForm(false)} aria-label="Close" className="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
              <X className="w-4 h-4 text-gray-600" />
            </button>
          </div>

          <form onSubmit={handleSubmit} className="space-y-4">
            {/* Type Grid */}
            <div>
              <p className="text-xs font-bold text-gray-500 uppercase mb-2">Issue Type <span className="text-red-500">*</span></p>
              <div className="grid grid-cols-2 gap-2">
                {COMPLAINT_CATEGORIES.map(({ type, icon: Icon, color }) => (
                  <button
                    key={type}
                    type="button"
                    onClick={() => setSelectedType(type)}
                    className={`flex items-center gap-2 px-3 py-3 rounded-xl border-2 text-left transition-all ${
                      selectedType === type
                        ? "border-[#0057A8] bg-[#E8F1FB]"
                        : "border-gray-200 bg-gray-50"
                    }`}
                  >
                    <div className={`w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 ${selectedType === type ? "bg-[#0057A8]/10" : "bg-gray-200"}`}>
                      <Icon className={`w-3.5 h-3.5 ${selectedType === type ? "text-[#0057A8]" : "text-gray-500"}`} />
                    </div>
                    <span className={`text-[11px] font-bold leading-tight ${selectedType === type ? "text-[#003F7F]" : "text-gray-600"}`}>{type}</span>
                  </button>
                ))}
              </div>
            </div>

            {/* Description */}
            <div>
              <label className="block text-xs font-bold text-gray-500 uppercase mb-1.5">Description <span className="text-red-500">*</span></label>
              <textarea
                value={description}
                onChange={(e) => setDescription(e.target.value)}
                placeholder="Describe the issue in detail…"
                rows={3}
                className="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-[#0057A8] focus:outline-none text-sm resize-none"
                required
              />
            </div>

            {/* Location with interactive map picker */}
            <div>
              <label className="block text-xs font-bold text-gray-500 uppercase mb-1.5">Location</label>

              {/* Two action buttons side by side */}
              <div className="flex gap-2 mb-2">
                <button
                  type="button"
                  onClick={() => setShowMap(true)}
                  className="flex-1 h-12 rounded-xl border-2 border-[#0057A8] bg-[#E8F1FB] flex items-center justify-center gap-2 text-[#0057A8] font-bold text-sm"
                >
                  <Map className="w-4 h-4" /> {locationCoords ? "Edit Pin" : "Pick on Map"}
                </button>
                <button
                  type="button"
                  onClick={handleGetLocation}
                  disabled={isLocating}
                  className="flex-1 h-12 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center gap-2 text-gray-600 font-bold text-sm disabled:opacity-60"
                >
                  {isLocating
                    ? <><Loader2 className="w-4 h-4 animate-spin" /> Locating…</>
                    : <><Navigation className="w-4 h-4" /> Use GPS</>}
                </button>
              </div>

              {/* Manual address fallback */}
              <input
                type="text"
                value={location}
                onChange={(e) => {
                  setLocation(e.target.value);
                  if (locationCoords) setLocationCoords(null);
                }}
                placeholder="Or type address e.g. Near main gate, Kinondoni"
                className="w-full h-12 px-4 rounded-xl border-2 border-gray-200 focus:border-[#0057A8] focus:outline-none text-sm"
              />

              {/* Pinned coords chip */}
              {locationCoords && (
                <div className="mt-2 flex items-center justify-between bg-[#E8F1FB] border border-[#0057A8]/30 rounded-xl px-3 py-2">
                  <div className="flex items-center gap-2">
                    <MapPin className="w-4 h-4 text-[#0057A8]" />
                    <span className="text-[#003F7F] text-xs font-mono font-bold">
                      {locationCoords.lat.toFixed(5)}, {locationCoords.lng.toFixed(5)}
                    </span>
                  </div>
                  <a
                    href={`https://www.google.com/maps?q=${locationCoords.lat},${locationCoords.lng}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex items-center gap-1 text-[10px] font-bold text-[#0057A8]"
                  >
                    <ExternalLink className="w-3 h-3" /> View
                  </a>
                </div>
              )}
            </div>

            {/* Contact Phone */}
            <div>
              <label className="block text-xs font-bold text-gray-500 uppercase mb-1.5">Contact Phone (optional)</label>
              <input
                type="tel"
                value={contactPhone}
                onChange={(e) => setContactPhone(e.target.value)}
                placeholder="+255 7XX XXX XXX"
                inputMode="tel"
                className="w-full h-12 px-4 rounded-xl border-2 border-gray-200 focus:border-[#0057A8] focus:outline-none text-sm"
              />
            </div>

            {/* Urgency */}
            <div>
              <p className="text-xs font-bold text-gray-500 uppercase mb-2">Urgency Level</p>
              <div className="grid grid-cols-4 gap-2">
                {URGENCY_LEVELS.map((u) => (
                  <button
                    key={u.value}
                    type="button"
                    onClick={() => setUrgency(u.value)}
                    className={`py-2.5 rounded-xl border-2 text-xs font-bold transition-all flex flex-col items-center gap-1 ${
                      urgency === u.value ? "border-[#0057A8] bg-[#E8F1FB] text-[#003F7F]" : "border-gray-200 text-gray-500"
                    }`}
                  >
                    <span className={`w-2 h-2 rounded-full ${u.dot}`} />
                    {u.label}
                  </button>
                ))}
              </div>
            </div>

            <div className="flex gap-2 pt-1">
              <button type="button" onClick={() => setShowForm(false)} className="flex-1 h-12 rounded-xl border-2 border-gray-200 text-gray-600 font-bold text-sm">
                Cancel
              </button>
              <button
                type="submit"
                disabled={isSubmitting}
                className="flex-1 h-12 rounded-xl bg-gradient-to-r from-[#0057A8] to-[#00AEEF] text-white font-black flex items-center justify-center gap-2 disabled:opacity-60"
              >
                {isSubmitting ? <Loader2 className="w-4 h-4 animate-spin" /> : <Send className="w-4 h-4" />}
                {isSubmitting ? "Submitting…" : "Submit"}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* Complaints List */}
      {complaintList.length === 0 ? (
        <div className="bg-white/10 border border-white/20 rounded-2xl p-8 text-center">
          <MessageSquare className="w-10 h-10 text-white/20 mx-auto mb-2" />
          <p className="text-white/60 text-sm">No complaints submitted yet.</p>
        </div>
      ) : (
        <div className="space-y-3">
          {complaintList.map((c) => {
            const isExp = expanded === c.id;
            return (
              <div key={c.id} className="bg-white/10 border border-white/20 rounded-2xl overflow-hidden">
                <button
                  onClick={() => setExpanded(isExp ? null : c.id)}
                  className="w-full p-4 text-left"
                >
                  <div className="flex items-start justify-between gap-2">
                    <div className="flex-1 min-w-0">
                      <p className="text-white font-bold text-sm leading-tight">{c.type}</p>
                      <p className="text-white/50 text-xs mt-0.5">{new Date(c.createdAt).toLocaleDateString()} · Meter {c.meterNumber}</p>
                    </div>
                    <div className="flex items-center gap-1.5 flex-shrink-0">
                      <span className={`flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full ${statusStyle(c.status)}`}>
                        <StatusIcon status={c.status} /> {c.status}
                      </span>
                      {isExp ? <ChevronUp className="w-4 h-4 text-white/40" /> : <ChevronDown className="w-4 h-4 text-white/40" />}
                    </div>
                  </div>
                </button>
                {isExp && (
                  <div className="border-t border-white/10 bg-black/10 p-4 space-y-2">
                    <p className="text-white/70 text-sm">{c.description}</p>
                    {c.location && (
                      (() => {
                        const coordMatch = c.location.match(/^(-?\d+\.\d+),\s*(-?\d+\.\d+)$/);
                        if (coordMatch) {
                          const [, lat, lng] = coordMatch;
                          return (
                            <a
                              href={`https://www.google.com/maps?q=${lat},${lng}`}
                              target="_blank"
                              rel="noopener noreferrer"
                              className="flex items-center gap-1.5 text-[#00AEEF] text-xs font-semibold"
                            >
                              <MapPin className="w-3.5 h-3.5 flex-shrink-0" />
                              View pinned location on Google Maps
                              <ExternalLink className="w-3 h-3" />
                            </a>
                          );
                        }
                        return (
                          <a
                            href={`https://www.google.com/maps/search/${encodeURIComponent(c.location)}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="flex items-center gap-1.5 text-[#00AEEF] text-xs font-semibold"
                          >
                            <MapPin className="w-3.5 h-3.5 flex-shrink-0" />
                            {c.location}
                            <ExternalLink className="w-3 h-3" />
                          </a>
                        );
                      })()
                    )}
                    {c.response && (
                      <div className="mt-2 bg-[#00AEEF]/10 border-l-4 border-[#00AEEF] rounded-r-xl p-3">
                        <p className="text-[#00AEEF] text-[10px] font-bold uppercase mb-1">DAWASA Response</p>
                        <p className="text-white/80 text-sm">{c.response}</p>
                      </div>
                    )}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}

      {/* Emergency */}
      <div className="bg-red-500/10 border-2 border-red-500/30 rounded-2xl p-4">
        <p className="text-red-300 font-bold text-sm flex items-center gap-2 mb-1">
          <AlertCircle className="w-4 h-4" /> Emergency Hotline
        </p>
        <p className="text-white/60 text-xs mb-2">Major leaks, water contamination — 24/7</p>
        <p className="text-red-300 font-black text-xl flex items-center gap-2">
          <Phone className="w-5 h-5" /> +255 22 245 3511
        </p>
      </div>
    </div>
  );
}
