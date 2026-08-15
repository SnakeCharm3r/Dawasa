'use client';

import { useEffect, useMemo, useState } from 'react';
import { useRouter } from 'next/navigation';
import { NavBar, type PageView } from '@/components/nav-bar';
import { useJournalData } from '@/hooks/use-journal-data';
import { useIdleSession } from '@/hooks/use-idle-session';
import { DashboardPage } from '@/components/pages/dashboard';
import { TradeLogPage } from '@/components/pages/trade-log';
import { CalendarPage } from '@/components/pages/calendar';
import { TradingAccountsPage } from '@/components/pages/trading-accounts';
import { SettingsPage } from '@/components/pages/settings';

export default function Journal() {
  const [view, setView] = useState<PageView>('dashboard');
  const journal = useJournalData();
  const router = useRouter();

  useEffect(() => {
    if (journal.authReady && !journal.user) router.replace('/login');
  }, [journal.authReady, journal.user, router]);

  useIdleSession({
    enabled: journal.authReady && Boolean(journal.user) && !journal.loading,
    timeoutMinutes: journal.settings.idle_timeout_minutes,
    onTimeout: journal.signOut,
  });

  const body = useMemo(() => {
    if (journal.loading) {
      return (
        <div className="flex min-h-[60vh] items-center justify-center">
          <div className="flex flex-col items-center gap-3 text-muted-foreground">
            <div className="h-8 w-8 animate-spin rounded-full border-2 border-muted border-t-foreground" />
            <p className="text-sm">Loading your journal…</p>
          </div>
        </div>
      );
    }

    if (journal.error) {
      return (
        <div className="mx-auto mt-20 max-w-md rounded-lg border border-destructive/50 bg-destructive/10 p-6 text-center">
          <p className="text-sm font-medium text-destructive">Couldn&apos;t load your journal</p>
          <p className="mt-1 text-xs text-muted-foreground">{journal.error}</p>
          <button
            onClick={() => journal.reload()}
            className="mt-4 rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90"
          >
            Try again
          </button>
        </div>
      );
    }

    if (view === 'dashboard') return <DashboardPage journal={journal} />;
    if (view === 'trades') return <TradeLogPage journal={journal} />;
    if (view === 'calendar') return <CalendarPage journal={journal} />;
    if (view === 'accounts') return <TradingAccountsPage demoMode={journal.demoMode} authenticated={!!journal.user} />;
    return <SettingsPage journal={journal} />;
  }, [view, journal]);

  if (!journal.authReady || journal.loading || !journal.user) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-background">
        <div className="flex flex-col items-center gap-3 text-muted-foreground">
          <div className="h-8 w-8 animate-spin rounded-full border-2 border-muted border-t-foreground" />
          <p className="text-sm">Checking your session…</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background">
      <NavBar
        view={view}
        onChange={setView}
        onSignOut={journal.signOut}
        userLabel={journal.profile?.display_name ?? journal.profile?.username ?? journal.user.email}
      />
      <main className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        {body}
      </main>
    </div>
  );
}
