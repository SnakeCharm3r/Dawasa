/// <reference types="vite/client" />
import { useState, useCallback, useEffect } from "react";
import {
  APIProvider,
  Map,
  AdvancedMarker,
  useMap,
  type MapMouseEvent,
} from "@vis.gl/react-google-maps";
import { Navigation, Loader2, MapPin, X } from "lucide-react";
import { toast } from "sonner";

const API_KEY = import.meta.env.VITE_GOOGLE_MAPS_API_KEY as string;

const DAR_ES_SALAAM = { lat: -6.7924, lng: 39.2083 };

interface Coords { lat: number; lng: number }

interface Props {
  value: Coords | null;
  onChange: (coords: Coords, label: string) => void;
  onClose: () => void;
}

function RecenterOnGPS({ trigger, onDone }: { trigger: boolean; onDone: (c: Coords) => void }) {
  const map = useMap();
  useEffect(() => {
    if (!trigger || !map) return;
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const c = { lat: pos.coords.latitude, lng: pos.coords.longitude };
        map.panTo(c);
        map.setZoom(17);
        onDone(c);
      },
      (err) => {
        toast.error(err.code === 1 ? "Location access denied." : "Could not get GPS location.");
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  }, [trigger, map, onDone]);
  return null;
}

export default function MapPicker({ value, onChange, onClose }: Props) {
  const initial = value ?? DAR_ES_SALAAM;
  const [pin, setPin] = useState<Coords>(initial);
  const [isLocating, setIsLocating] = useState(false);
  const [gpsTrigger, setGpsTrigger] = useState(false);

  const handleMapClick = useCallback((e: MapMouseEvent) => {
    if (!e.detail.latLng) return;
    const c = { lat: e.detail.latLng.lat, lng: e.detail.latLng.lng };
    setPin(c);
  }, []);

  const handleGPS = () => {
    if (!navigator.geolocation) { toast.error("Geolocation not supported."); return; }
    setIsLocating(true);
    setGpsTrigger(t => !t);
  };

  const handleGPSDone = useCallback((c: Coords) => {
    setPin(c);
    setIsLocating(false);
  }, []);

  const handleConfirm = () => {
    onChange(pin, `${pin.lat.toFixed(6)}, ${pin.lng.toFixed(6)}`);
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 flex flex-col bg-black">
      {/* Top bar */}
      <div className="flex items-center justify-between px-4 py-3 bg-[#003F7F] safe-top">
        <button onClick={onClose} aria-label="Close map" className="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center">
          <X className="w-5 h-5 text-white" />
        </button>
        <div className="text-center">
          <p className="text-white font-black text-sm">Pin Location</p>
          <p className="text-white/50 text-[10px]">Tap the map or drag the pin</p>
        </div>
        <button
          onClick={handleGPS}
          disabled={isLocating}
          aria-label="Use GPS"
          className="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center disabled:opacity-50"
        >
          {isLocating
            ? <Loader2 className="w-4 h-4 text-white animate-spin" />
            : <Navigation className="w-4 h-4 text-white" />}
        </button>
      </div>

      {/* Map */}
      <div className="flex-1 relative">
        <APIProvider apiKey={API_KEY}>
          <Map
            defaultCenter={initial}
            defaultZoom={value ? 16 : 13}
            mapId="dawasa-map"
            gestureHandling="greedy"
            disableDefaultUI={false}
            onClick={handleMapClick}
            style={{ width: "100%", height: "100%" }}
          >
            <AdvancedMarker
              position={pin}
              draggable
              onDragEnd={(e) => {
                if (!e.latLng) return;
                setPin({ lat: e.latLng.lat(), lng: e.latLng.lng() });
              }}
            />
            <RecenterOnGPS trigger={gpsTrigger} onDone={handleGPSDone} />
          </Map>
        </APIProvider>

        {/* Coords overlay */}
        <div className="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black/70 backdrop-blur-sm rounded-full px-4 py-1.5 flex items-center gap-2 pointer-events-none">
          <MapPin className="w-3.5 h-3.5 text-[#00AEEF]" />
          <span className="text-white text-xs font-mono">
            {pin.lat.toFixed(5)}, {pin.lng.toFixed(5)}
          </span>
        </div>
      </div>

      {/* Confirm button */}
      <div className="px-4 py-4 bg-[#003F7F] safe-bottom">
        <button
          onClick={handleConfirm}
          className="w-full h-14 bg-white text-[#003F7F] rounded-2xl font-black text-base flex items-center justify-center gap-2"
        >
          <MapPin className="w-5 h-5" /> Confirm This Location
        </button>
      </div>
    </div>
  );
}
