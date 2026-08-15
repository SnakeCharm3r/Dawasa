'use client';

import { useEffect, useRef } from 'react';

const LAST_ACTIVITY_KEY = 'trading-journal:last-activity';
const ACTIVITY_WRITE_INTERVAL_MS = 5_000;

export function idleDeadline(lastActivity: number, timeoutMinutes: number): number {
  return lastActivity + timeoutMinutes * 60_000;
}

export function isSessionIdle(lastActivity: number, timeoutMinutes: number, now = Date.now()): boolean {
  return timeoutMinutes > 0 && now >= idleDeadline(lastActivity, timeoutMinutes);
}

type Options = {
  enabled: boolean;
  timeoutMinutes: number;
  onTimeout: () => Promise<void> | void;
};

export function useIdleSession({ enabled, timeoutMinutes, onTimeout }: Options) {
  const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const lastWriteRef = useRef(0);
  const expiringRef = useRef(false);

  useEffect(() => {
    if (!enabled || timeoutMinutes <= 0) return;

    const clearTimer = () => {
      if (timeoutRef.current) clearTimeout(timeoutRef.current);
      timeoutRef.current = null;
    };

    const readLastActivity = () => {
      const stored = Number(window.localStorage.getItem(LAST_ACTIVITY_KEY));
      return Number.isFinite(stored) && stored > 0 ? stored : Date.now();
    };

    const expire = () => {
      if (expiringRef.current) return;
      expiringRef.current = true;
      clearTimer();
      void Promise.resolve(onTimeout()).finally(() => {
        window.localStorage.removeItem(LAST_ACTIVITY_KEY);
      });
    };

    const armTimer = () => {
      clearTimer();
      const lastActivity = readLastActivity();
      if (isSessionIdle(lastActivity, timeoutMinutes)) {
        expire();
        return;
      }
      const remaining = Math.max(250, idleDeadline(lastActivity, timeoutMinutes) - Date.now());
      timeoutRef.current = setTimeout(armTimer, remaining);
    };

    const recordActivity = () => {
      const now = Date.now();
      if (now - lastWriteRef.current < ACTIVITY_WRITE_INTERVAL_MS) return;
      lastWriteRef.current = now;
      window.localStorage.setItem(LAST_ACTIVITY_KEY, String(now));
      armTimer();
    };

    const handleStorage = (event: StorageEvent) => {
      if (event.key === LAST_ACTIVITY_KEY) armTimer();
    };

    const handleVisibility = () => {
      if (document.visibilityState === 'visible') armTimer();
    };

    expiringRef.current = false;
    lastWriteRef.current = Date.now();
    window.localStorage.setItem(LAST_ACTIVITY_KEY, String(lastWriteRef.current));
    armTimer();

    const activityEvents: Array<keyof WindowEventMap> = ['pointerdown', 'keydown', 'scroll', 'touchstart'];
    activityEvents.forEach((event) => window.addEventListener(event, recordActivity, { passive: true }));
    window.addEventListener('storage', handleStorage);
    document.addEventListener('visibilitychange', handleVisibility);

    return () => {
      clearTimer();
      activityEvents.forEach((event) => window.removeEventListener(event, recordActivity));
      window.removeEventListener('storage', handleStorage);
      document.removeEventListener('visibilitychange', handleVisibility);
    };
  }, [enabled, onTimeout, timeoutMinutes]);
}
