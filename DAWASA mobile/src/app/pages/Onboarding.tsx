import { useState, useRef } from "react";
import { useNavigate } from "react-router";
import {
  Droplet,
  FileText,
  CreditCard,
  MessageSquare,
  ChevronRight,
  Hash,
  CheckCircle2,
  Phone,
  MapPin,
} from "lucide-react";

const SLIDES = [
  {
    id: 0,
    badge: "Welcome",
    title: "Dawasa\nKiganjani",
    subtitle: "Dar-es-Salaam Water Supply\nand Sanitation Authority",
    body: "Manage your water account anywhere, anytime — no login required.",
    visual: "welcome",
    accent: "#00AEEF",
  },
  {
    id: 1,
    badge: "Bills & Payments",
    title: "View Bills &\nPay Easily",
    subtitle: "All your water billing in one place",
    body: "Enter your meter number to instantly access your full bill statement, download CSV reports, and generate a control number to pay via M-Pesa, Tigo Pesa, Airtel Money, or bank transfer.",
    visual: "bills",
    accent: "#00AEEF",
  },
  {
    id: 2,
    badge: "Support",
    title: "Report Issues &\nGet Feedback",
    subtitle: "Your voice matters",
    body: "Submit complaints about water pressure, billing errors, leaks, or water quality. Pin your location on a map, track status, and receive official DAWASA responses.",
    visual: "complaints",
    accent: "#00A651",
  },
];

function WelcomeVisual() {
  return (
    <div className="flex flex-col items-center justify-center h-full gap-6">
      <div className="relative">
        <div className="w-32 h-32 rounded-[2.5rem] bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center shadow-2xl">
          <Droplet className="w-16 h-16 text-white" fill="white" />
        </div>
        <div className="absolute -top-2 -right-2 w-8 h-8 rounded-full bg-[#00AEEF] flex items-center justify-center shadow-lg">
          <CheckCircle2 className="w-4 h-4 text-white" />
        </div>
      </div>
      <div className="flex gap-3 mt-2">
        {["Kinondoni", "Ilala", "Ubungo", "Temeke"].map((zone) => (
          <div key={zone} className="bg-white/10 border border-white/20 rounded-full px-3 py-1">
            <span className="text-white/70 text-[10px] font-semibold">{zone}</span>
          </div>
        ))}
      </div>
    </div>
  );
}

function BillsVisual() {
  return (
    <div className="flex flex-col gap-3 w-full px-2">
      {/* Meter input mockup */}
      <div className="bg-white/10 border border-white/20 rounded-2xl p-4 flex items-center gap-3">
        <div className="w-9 h-9 rounded-xl bg-[#00AEEF]/20 flex items-center justify-center flex-shrink-0">
          <Hash className="w-4 h-4 text-[#00AEEF]" />
        </div>
        <div className="flex-1">
          <p className="text-white/40 text-[10px] uppercase font-bold mb-0.5">Meter Number</p>
          <p className="text-white font-mono font-bold text-sm">12345678</p>
        </div>
      </div>

      {/* Bill card mockup */}
      <div className="bg-white/10 border border-white/20 rounded-2xl p-4">
        <div className="flex justify-between items-start mb-3">
          <div>
            <p className="text-white/40 text-[10px] uppercase font-bold">February 2026</p>
            <p className="text-white font-black text-xl">102,200 <span className="text-white/40 text-xs font-normal">TZS</span></p>
          </div>
          <span className="bg-yellow-400/20 text-yellow-300 text-[10px] font-bold px-2 py-0.5 rounded-full">Pending</span>
        </div>
        <div className="flex gap-2">
          <div className="flex-1 bg-white/5 rounded-xl p-2 text-center">
            <p className="text-white/40 text-[9px] uppercase">Usage</p>
            <p className="text-[#00AEEF] font-black text-sm">25 m³</p>
          </div>
          <div className="flex-1 bg-white/5 rounded-xl p-2 text-center">
            <p className="text-white/40 text-[9px] uppercase">Control No.</p>
            <p className="text-white font-mono font-bold text-xs">990012...</p>
          </div>
        </div>
      </div>

      {/* Payment methods mockup */}
      <div className="grid grid-cols-4 gap-1.5">
        {[
          { label: "M-Pesa", color: "bg-green-500/20 text-green-300" },
          { label: "Tigo",   color: "bg-blue-500/20 text-blue-300" },
          { label: "Airtel", color: "bg-red-500/20 text-red-300" },
          { label: "Bank",   color: "bg-white/10 text-white/60" },
        ].map((m) => (
          <div key={m.label} className={`${m.color} rounded-xl py-2 text-center`}>
            <p className="text-[10px] font-bold">{m.label}</p>
          </div>
        ))}
      </div>

      {/* CTA icon row */}
      <div className="flex justify-center gap-4 pt-1">
        {[FileText, CreditCard].map((Icon, i) => (
          <div key={i} className="w-10 h-10 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center">
            <Icon className="w-4 h-4 text-white/60" />
          </div>
        ))}
      </div>
    </div>
  );
}

function ComplaintsVisual() {
  return (
    <div className="flex flex-col gap-3 w-full px-2">
      {/* Issue type chips */}
      <div className="flex flex-wrap gap-2">
        {[
          { label: "Low Pressure", color: "bg-blue-400/20 text-blue-300" },
          { label: "Water Leakage", color: "bg-[#00AEEF]/20 text-[#00AEEF]" },
          { label: "Billing Error", color: "bg-red-400/20 text-red-300" },
          { label: "No Supply", color: "bg-red-500/20 text-red-400" },
          { label: "Water Quality", color: "bg-yellow-400/20 text-yellow-300" },
        ].map((t) => (
          <span key={t.label} className={`${t.color} text-[10px] font-bold px-2.5 py-1 rounded-full border border-white/10`}>
            {t.label}
          </span>
        ))}
      </div>

      {/* Location card */}
      <div className="bg-white/10 border border-white/20 rounded-2xl p-3 flex items-center gap-3">
        <div className="w-9 h-9 rounded-xl bg-[#00A651]/20 flex items-center justify-center flex-shrink-0">
          <MapPin className="w-4 h-4 text-[#00A651]" />
        </div>
        <div>
          <p className="text-white/40 text-[10px] uppercase font-bold">Location Pinned</p>
          <p className="text-white font-mono text-xs">-6.79240, 39.20830</p>
        </div>
      </div>

      {/* Status cards */}
      <div className="space-y-2">
        {[
          { ref: "CMP-001", status: "In Progress", color: "bg-[#00AEEF]/20 text-[#00AEEF]", type: "Low Pressure" },
          { ref: "CMP-002", status: "Resolved",    color: "bg-[#00A651]/20 text-[#00A651]", type: "Billing Error" },
        ].map((c) => (
          <div key={c.ref} className="bg-white/5 border border-white/10 rounded-xl px-3 py-2 flex items-center justify-between">
            <div>
              <p className="text-white font-semibold text-xs">{c.type}</p>
              <p className="text-white/40 text-[10px]">{c.ref}</p>
            </div>
            <span className={`${c.color} text-[10px] font-bold px-2 py-0.5 rounded-full`}>{c.status}</span>
          </div>
        ))}
      </div>

      {/* Hotline */}
      <div className="bg-red-500/10 border border-red-500/20 rounded-xl px-3 py-2 flex items-center gap-2">
        <Phone className="w-3.5 h-3.5 text-red-300 flex-shrink-0" />
        <p className="text-red-300 font-bold text-xs">Emergency: +255 22 245 3511</p>
      </div>

      {/* Icon row */}
      <div className="flex justify-center gap-4 pt-1">
        {[MessageSquare].map((Icon, i) => (
          <div key={i} className="w-10 h-10 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center">
            <Icon className="w-4 h-4 text-white/60" />
          </div>
        ))}
      </div>
    </div>
  );
}

export default function Onboarding() {
  const navigate = useNavigate();
  const [current, setCurrent] = useState(0);
  const touchStartX = useRef<number | null>(null);
  const isLast = current === SLIDES.length - 1;

  const goNext = () => {
    if (isLast) {
      localStorage.setItem("dawasa_onboarded", "1");
      navigate("/", { replace: true });
    } else {
      setCurrent((c) => c + 1);
    }
  };

  const goTo = (idx: number) => setCurrent(idx);

  const handleTouchStart = (e: React.TouchEvent) => {
    touchStartX.current = e.touches[0].clientX;
  };

  const handleTouchEnd = (e: React.TouchEvent) => {
    if (touchStartX.current === null) return;
    const dx = e.changedTouches[0].clientX - touchStartX.current;
    touchStartX.current = null;
    if (dx < -50 && current < SLIDES.length - 1) setCurrent((c) => c + 1);
    if (dx > 50  && current > 0)                  setCurrent((c) => c - 1);
  };

  const slide = SLIDES[current];

  return (
    <div
      className="min-h-screen bg-gradient-to-br from-[#003F7F] via-[#0057A8] to-[#005f8e] flex flex-col overflow-hidden"
      style={{ "--accent": slide.accent } as React.CSSProperties}
      onTouchStart={handleTouchStart}
      onTouchEnd={handleTouchEnd}
    >
      {/* Safe area top */}
      <div className="h-12" />

      {/* Skip */}
      {!isLast && (
        <div className="flex justify-end px-6">
          <button
            onClick={() => { localStorage.setItem("dawasa_onboarded", "1"); navigate("/", { replace: true }); }}
            className="text-white/40 text-sm font-semibold py-1"
          >
            Skip
          </button>
        </div>
      )}

      {/* Visual area */}
      <div className="flex-1 flex flex-col items-center justify-center px-6 pt-4 pb-2 min-h-0">
        <div className="w-full max-w-sm h-64 flex items-center justify-center">
          {slide.visual === "welcome"    && <WelcomeVisual />}
          {slide.visual === "bills"      && <BillsVisual />}
          {slide.visual === "complaints" && <ComplaintsVisual />}
        </div>
      </div>

      {/* Text content */}
      <div className="px-6 pb-2">
        <div className="max-w-sm mx-auto">
          {/* Badge */}
          <div className="mb-3">
            <span
              className="text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full border [color:var(--accent)] [border-color:color-mix(in_srgb,var(--accent)_25%,transparent)] [background-color:color-mix(in_srgb,var(--accent)_15%,transparent)]"
            >
              {slide.badge}
            </span>
          </div>

          {/* Title */}
          <h1 className="text-3xl font-black text-white leading-tight mb-2 whitespace-pre-line">
            {slide.title}
          </h1>

          {/* Subtitle */}
          <p className="text-sm font-semibold whitespace-pre-line mb-3 [color:var(--accent)]">
            {slide.subtitle}
          </p>

          {/* Body */}
          <p className="text-white/60 text-sm leading-relaxed">
            {slide.body}
          </p>
        </div>
      </div>

      {/* Bottom controls */}
      <div className="px-6 pb-10 pt-6">
        <div className="max-w-sm mx-auto flex items-center justify-between">
          {/* Dot indicators */}
          <div className="flex gap-2">
            {SLIDES.map((_, i) => (
              <button
                key={i}
                onClick={() => goTo(i)}
                className="transition-all duration-300"
                aria-label={`Go to slide ${i + 1}`}
              >
                <div
                  className={`rounded-full transition-all duration-300 h-2 ${
                    i === current
                      ? "w-6 [background-color:var(--accent)]"
                      : "w-2 bg-white/25"
                  }`}
                />
              </button>
            ))}
          </div>

          {/* Next / Get Started button */}
          <button
            onClick={goNext}
            className={`flex items-center gap-2 font-black text-sm px-6 py-3.5 rounded-2xl shadow-xl active:scale-95 transition-all duration-200 text-white ${
              isLast
                ? "bg-gradient-to-br from-[#00A651] to-[#00c562]"
                : current === 0
                  ? "bg-gradient-to-br from-[#00AEEF] to-[#0057A8]"
                  : "bg-gradient-to-br from-[#00AEEF] to-[#0057A8]"
            }`}
          >
            {isLast ? "Get Started" : "Next"}
            <ChevronRight className="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>
  );
}
