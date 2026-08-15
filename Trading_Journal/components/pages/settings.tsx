'use client';

import { FormEvent, useEffect, useState } from 'react';
import { Clock3, LogOut, ShieldCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import type { useJournalData } from '@/hooks/use-journal-data';

type Journal = ReturnType<typeof useJournalData>;

const TIMEOUT_OPTIONS = [
  { value: 15, label: '15 minutes' },
  { value: 30, label: '30 minutes' },
  { value: 60, label: '1 hour' },
  { value: 120, label: '2 hours' },
  { value: 240, label: '4 hours' },
  { value: 0, label: 'Never automatically sign out' },
];

export function SettingsPage({ journal }: { journal: Journal }) {
  const [timeoutMinutes, setTimeoutMinutes] = useState(journal.settings.idle_timeout_minutes);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => setTimeoutMinutes(journal.settings.idle_timeout_minutes), [journal.settings.idle_timeout_minutes]);

  const save = async (event: FormEvent) => {
    event.preventDefault();
    setSaving(true);
    setMessage(null);
    try {
      await journal.saveSettings({ idle_timeout_minutes: timeoutMinutes });
      setMessage('Session timeout updated.');
    } catch (error) {
      setMessage(error instanceof Error ? error.message : 'Could not update the timeout.');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">Settings</h1>
        <p className="text-sm text-muted-foreground">Manage security and session behaviour for your journal.</p>
      </div>

      <div className="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(300px,0.8fr)]">
        <Card className="p-5">
          <div className="flex items-start gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
              <Clock3 className="h-5 w-5" />
            </div>
            <div>
              <h2 className="font-semibold">Idle session timeout</h2>
              <p className="mt-1 text-sm text-muted-foreground">
                Automatically sign out this account when there has been no keyboard, pointer, touch, or scroll activity.
              </p>
            </div>
          </div>

          <form onSubmit={save} className="mt-6 max-w-md space-y-4">
            <div className="space-y-1.5">
              <Label htmlFor="idle-timeout">Sign out after</Label>
              <select
                id="idle-timeout"
                value={timeoutMinutes}
                onChange={(event) => setTimeoutMinutes(Number(event.target.value))}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
              >
                {TIMEOUT_OPTIONS.map((option) => (
                  <option key={option.value} value={option.value}>{option.label}</option>
                ))}
              </select>
            </div>
            <Button disabled={saving}>{saving ? 'Saving…' : 'Save session settings'}</Button>
            {message && <p className="text-sm text-muted-foreground">{message}</p>}
          </form>
        </Card>

        <Card className="p-5">
          <div className="flex items-center gap-2">
            <ShieldCheck className="h-4 w-4 text-emerald-500" />
            <h2 className="font-semibold">Current session</h2>
          </div>
          <dl className="mt-5 space-y-4 text-sm">
            <div>
              <dt className="text-xs text-muted-foreground">Signed in as</dt>
              <dd className="mt-1 break-all font-medium">{journal.user?.email ?? 'Authenticated user'}</dd>
            </div>
            <div>
              <dt className="text-xs text-muted-foreground">Session handling</dt>
              <dd className="mt-1">Secure refresh with activity-based local logout</dd>
            </div>
          </dl>
          <Button variant="outline" className="mt-6 w-full" onClick={() => journal.signOut()}>
            <LogOut className="mr-2 h-4 w-4" /> Sign out of this session
          </Button>
        </Card>
      </div>
    </div>
  );
}
