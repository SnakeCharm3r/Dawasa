'use client';

import { FormEvent, useCallback, useEffect, useState } from 'react';
import { AlertCircle, CheckCircle2, KeyRound, Link2, LockKeyhole, RefreshCw, Server, Unplug } from 'lucide-react';
import { invokeJournalApi } from '@/lib/edge-api';
import { BROKER_IDS, type BrokerId } from '@/lib/brokers';
import type { TradingAccount } from '@/lib/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = { demoMode: boolean; authenticated: boolean };

type PairingDetails = { accountId: string; code: string; expiresAt: string };

export function TradingAccountsPage({ demoMode, authenticated }: Props) {
  const [accounts, setAccounts] = useState<TradingAccount[]>([]);
  const [loading, setLoading] = useState(!demoMode);
  const [busyId, setBusyId] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [broker, setBroker] = useState<BrokerId>('Exness');
  const [accountNumber, setAccountNumber] = useState('');
  const [password, setPassword] = useState('');
  const [server, setServer] = useState('');
  const [accountName, setAccountName] = useState('');
  const [pairing, setPairing] = useState<PairingDetails | null>(null);

  const load = useCallback(async () => {
    if (demoMode || !authenticated) return;
    setLoading(true);
    setError(null);
    try {
      const body = await invokeJournalApi<{ accounts: TradingAccount[] }>({ action: 'list_accounts' });
      setAccounts(body.accounts);
    } catch (loadError) {
      setError(loadError instanceof Error ? loadError.message : 'Could not load accounts.');
    } finally {
      setLoading(false);
    }
  }, [authenticated, demoMode]);

  useEffect(() => {
    load();
  }, [load]);

  const register = async (event: FormEvent) => {
    event.preventDefault();
    setError(null);
    try {
      await invokeJournalApi({
        action: 'create_account',
        broker,
        account_number: accountNumber,
        password,
        server,
        account_name: accountName || null,
        history_days: 90,
      });
      setAccountNumber('');
      setPassword('');
      setServer('');
      setAccountName('');
      await load();
    } catch (registerError) {
      setError(registerError instanceof Error ? registerError.message : 'Could not register account.');
    }
  };

  const action = async (account: TradingAccount, kind: 'sync' | 'resync' | 'disconnect') => {
    setBusyId(account.id);
    setError(null);
    try {
      await invokeJournalApi({
        action: kind === 'disconnect' ? 'disconnect_account' : kind === 'resync' ? 'request_resync' : 'request_sync',
        account_id: account.id,
        ...(kind === 'resync' ? { days: 90 } : {}),
      });
      await load();
    } catch (actionError) {
      setError(actionError instanceof Error ? actionError.message : 'Account action failed.');
    } finally {
      setBusyId(null);
    }
  };

  const createPairingCode = async (account: TradingAccount) => {
    setBusyId(account.id);
    setError(null);
    try {
      const body = await invokeJournalApi<{ pairing_code: string; expires_at: string }>({
        action: 'pair_connector',
        account_id: account.id,
      });
      setPairing({ accountId: account.id, code: body.pairing_code, expiresAt: body.expires_at });
      await load();
    } catch (pairError) {
      setError(pairError instanceof Error ? pairError.message : 'Could not create pairing code.');
    } finally {
      setBusyId(null);
    }
  };

  if (demoMode) {
    return (
      <Card className="p-6">
        <h1 className="text-2xl font-semibold">Trading Accounts</h1>
        <p className="mt-2 text-sm text-muted-foreground">
          Configure Supabase first, then sign in to register an MT5 broker account.
        </p>
      </Card>
    );
  }

  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">Trading Accounts</h1>
        <p className="text-sm text-muted-foreground">Add your broker login details and prepare accounts for MT5 synchronization.</p>
      </div>

      {error && (
        <div className="flex items-center gap-2 rounded-md border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive">
          <AlertCircle className="h-4 w-4" /> {error}
        </div>
      )}

      <Card className="p-5">
        <div className="flex items-center gap-2">
          <Link2 className="h-4 w-4" />
          <h2 className="font-semibold">Add broker account</h2>
        </div>
        <p className="mt-1 text-xs text-muted-foreground">
          Use a read-only investor password when your broker provides one. Passwords are encrypted on the server and never returned to the browser.
        </p>
        <form onSubmit={register} className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
          <div className="space-y-1.5">
            <Label htmlFor="broker">Broker</Label>
            <select
              id="broker"
              value={broker}
              onChange={(event) => setBroker(event.target.value as BrokerId)}
              className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            >
              {BROKER_IDS.map((brokerName) => <option key={brokerName} value={brokerName}>{brokerName}</option>)}
            </select>
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="account-number">MT5 account ID</Label>
            <Input id="account-number" required value={accountNumber} onChange={(e) => setAccountNumber(e.target.value)} />
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="broker-password">Password</Label>
            <div className="relative">
              <LockKeyhole className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                id="broker-password"
                type="password"
                autoComplete="new-password"
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="pl-9"
              />
            </div>
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="server">Server</Label>
            <Input id="server" required placeholder="Broker-MT5Real" value={server} onChange={(e) => setServer(e.target.value)} />
          </div>
          <div className="space-y-1.5 sm:col-span-2 xl:col-span-1">
            <Label htmlFor="account-name">Account name</Label>
            <Input id="account-name" value={accountName} onChange={(e) => setAccountName(e.target.value)} />
          </div>
          <div className="flex items-end sm:col-span-2 xl:col-span-5 xl:justify-end">
            <Button className="w-full xl:w-auto">Add account</Button>
          </div>
        </form>
      </Card>

      {loading ? (
        <p className="text-sm text-muted-foreground">Loading accounts…</p>
      ) : accounts.length === 0 ? (
        <Card className="p-8 text-center text-sm text-muted-foreground">No trading accounts registered yet.</Card>
      ) : (
        <div className="grid gap-4 lg:grid-cols-2">
          {accounts.map((account) => (
            <Card key={account.id} className="p-5">
              <div className="flex items-start justify-between gap-4">
                <div className="flex gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-muted"><Server className="h-5 w-5" /></div>
                  <div>
                    <div className="font-semibold">{account.broker} / {account.platform}</div>
                    <div className="text-sm text-muted-foreground">{account.account_number} · {account.server}</div>
                  </div>
                </div>
                <StatusBadge status={account.sync_status} />
              </div>
              <dl className="mt-5 grid grid-cols-2 gap-3 text-sm">
                <Stat label="Last successful sync" value={account.last_sync_at ? new Date(account.last_sync_at).toLocaleString() : 'Never'} />
                <Stat label="Imported trades" value={String(account.imported_trade_count)} />
                <Stat label="Currency" value={account.account_currency ?? '—'} />
                <Stat label="Balance / Equity" value={account.balance == null ? '—' : `${account.balance} / ${account.equity ?? '—'}`} />
              </dl>
              {account.sync_error && <p className="mt-3 text-xs text-destructive">{account.sync_error}</p>}
              <div className="mt-5 flex flex-wrap gap-2">
                <Button variant="outline" size="sm" disabled={busyId === account.id} onClick={() => createPairingCode(account)}>
                  <KeyRound className="mr-1.5 h-3.5 w-3.5" /> Pair connector
                </Button>
                <Button size="sm" disabled={busyId === account.id} onClick={() => action(account, 'sync')}>
                  <RefreshCw className="mr-1.5 h-3.5 w-3.5" /> Sync Now
                </Button>
                <Button variant="outline" size="sm" disabled={busyId === account.id} onClick={() => action(account, 'resync')}>Re-sync 90 days</Button>
                <Button variant="ghost" size="sm" disabled={busyId === account.id} onClick={() => action(account, 'disconnect')}>
                  <Unplug className="mr-1.5 h-3.5 w-3.5" /> Disconnect
                </Button>
              </div>
              {pairing?.accountId === account.id && (
                <div className="mt-4 rounded-md border border-primary/30 bg-primary/5 p-3">
                  <p className="text-xs font-medium">Run this on the computer hosting MetaTrader 5:</p>
                  <code className="mt-2 block break-all rounded bg-background p-2 text-xs">
                    python -m mt5_sync_service.main --pair {pairing.code}
                  </code>
                  <p className="mt-2 text-[11px] text-muted-foreground">
                    Single-use code expires {new Date(pairing.expiresAt).toLocaleString()}. Your MT5 password is requested privately on that computer.
                  </p>
                </div>
              )}
              <p className="mt-3 break-all text-[10px] text-muted-foreground">Sync service account ID: {account.id}</p>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}

function StatusBadge({ status }: { status: TradingAccount['sync_status'] }) {
  const ok = status === 'connected';
  return (
    <Badge variant="secondary" className={ok ? 'bg-emerald-500/15 text-emerald-600' : status === 'syncing' ? 'bg-blue-500/15 text-blue-600' : 'bg-muted text-muted-foreground'}>
      {ok && <CheckCircle2 className="mr-1 h-3 w-3" />}
      {status === 'terminal_offline' ? 'MT5 Terminal Offline' : status.replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase())}
    </Badge>
  );
}

function Stat({ label, value }: { label: string; value: string }) {
  return <div><dt className="text-xs text-muted-foreground">{label}</dt><dd className="mt-0.5 font-medium">{value}</dd></div>;
}
