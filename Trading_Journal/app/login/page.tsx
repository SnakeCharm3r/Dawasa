'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { LineChart, ShieldCheck } from 'lucide-react';
import { AuthPanel } from '@/components/auth-panel';
import { Card } from '@/components/ui/card';
import { useJournalData } from '@/hooks/use-journal-data';

export default function LoginPage() {
  const journal = useJournalData();
  const router = useRouter();

  useEffect(() => {
    if (journal.authReady && journal.user) router.replace('/journal');
  }, [journal.authReady, journal.user, router]);

  if (journal.demoMode) {
    return (
      <main className="flex min-h-screen items-center justify-center bg-muted/30 px-4">
        <Card className="max-w-md p-6 text-center">
          <h1 className="text-lg font-semibold">Authentication is not configured</h1>
          <p className="mt-2 text-sm text-muted-foreground">
            Set the public Supabase URL and publishable key, then restart the application.
          </p>
        </Card>
      </main>
    );
  }

  if (!journal.authReady || journal.user) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-background">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-muted border-t-foreground" />
      </div>
    );
  }

  return (
    <main className="grid min-h-screen bg-background lg:grid-cols-[minmax(0,1.15fr)_minmax(420px,0.85fr)]">
      <section className="relative flex min-h-[270px] overflow-hidden bg-[#03130d] px-7 py-8 text-white sm:min-h-[330px] sm:px-12 lg:min-h-screen lg:items-end lg:px-16 lg:py-16">
        <div className="absolute -left-32 top-[-20%] h-[36rem] w-[36rem] rounded-full bg-emerald-500/20 blur-[130px]" />
        <div className="absolute -bottom-48 right-[-15%] h-[34rem] w-[34rem] rounded-full bg-green-700/30 blur-[130px]" />
        <div className="absolute inset-0 bg-[linear-gradient(145deg,rgba(1,8,5,0.05),rgba(0,0,0,0.72))]" />
        <div className="absolute inset-x-[12%] top-[22%] h-px bg-gradient-to-r from-transparent via-emerald-400/30 to-transparent" />

        <div className="relative z-10 max-w-xl self-end">
          <div className="mb-7 flex h-12 w-12 items-center justify-center rounded-2xl border border-emerald-300/20 bg-emerald-400/10 text-emerald-300 shadow-[0_0_45px_rgba(16,185,129,0.22)] backdrop-blur">
            <LineChart className="h-6 w-6" />
          </div>
          <h1 className="max-w-lg text-4xl font-semibold tracking-[-0.04em] sm:text-5xl lg:text-6xl">
            Trading Journal <span className="text-emerald-400">application</span>
          </h1>
          <p className="mt-5 max-w-md text-sm leading-6 text-emerald-50/60 sm:text-base">
            A focused workspace for reviewing decisions, measuring performance, and building consistency.
          </p>
          <div className="mt-8 flex items-center gap-2 text-xs font-medium uppercase tracking-[0.18em] text-emerald-200/65">
            <ShieldCheck className="h-4 w-4" /> Private by design
          </div>
        </div>
      </section>

      <section className="flex items-center justify-center px-6 py-12 sm:px-10 lg:px-14">
        <div className="w-full max-w-md">
          <div className="mb-10 flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-foreground text-background">
              <LineChart className="h-5 w-5" />
            </div>
            <div>
              <div className="text-sm font-semibold">Trading Journal</div>
              <div className="text-xs text-muted-foreground">Secure access</div>
            </div>
          </div>
          <AuthPanel
            signInWithPassword={journal.signInWithPassword}
            signUpWithPassword={journal.signUpWithPassword}
            signInWithGoogle={journal.signInWithGoogle}
          />
          <p className="mt-8 text-center text-xs leading-5 text-muted-foreground">
            Your journal data is protected by account-level access controls.
          </p>
        </div>
      </section>
    </main>
  );
}
