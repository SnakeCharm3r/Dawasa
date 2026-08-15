'use client';

import { useMemo, useState } from 'react';
import {
  ResponsiveContainer,
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  BarChart,
  Bar,
  Cell,
  ReferenceLine,
} from 'recharts';
import {
  Activity,
  TrendingUp,
  TrendingDown,
  Wallet,
  Target,
  Percent,
  Scale,
  Gauge,
  Save,
  Plus,
  Trash2,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card } from '@/components/ui/card';
import { computeStats } from '@/lib/calc';
import { fmtMoney, fmtNum, fmtPct, moneyColor } from '@/lib/format';
import type { useJournalData } from '@/hooks/use-journal-data';

type Journal = ReturnType<typeof useJournalData>;

export function DashboardPage({ journal }: { journal: Journal }) {
  const { tradesWithCalc, settings, strategies, saveSettings, addStrategy, deleteStrategy } = journal;
  const stats = useMemo(() => computeStats(tradesWithCalc, settings), [tradesWithCalc, settings]);

  const equityData = useMemo(() => {
    const points = [{ name: 'Start', balance: settings.starting_balance }];
    tradesWithCalc.forEach((t, i) => {
      points.push({ name: `#${t.trade_number}`, balance: t.calc.accountBalance });
    });
    return points;
  }, [tradesWithCalc, settings.starting_balance]);

  const strategyData = useMemo(() => {
    const map = new Map<string, number>();
    tradesWithCalc.forEach((t) => {
      map.set(t.strategy, (map.get(t.strategy) ?? 0) + t.calc.netPnl);
    });
    return Array.from(map.entries())
      .map(([name, net]) => ({ name, net: Math.round(net * 100) / 100 }))
      .sort((a, b) => b.net - a.net);
  }, [tradesWithCalc]);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">Dashboard</h1>
        <p className="text-sm text-muted-foreground">Performance overview across all your trades.</p>
      </div>

      <StatGrid stats={stats} />

      <div className="grid gap-6 lg:grid-cols-3">
        <Card className="p-5 lg:col-span-2">
          <div className="mb-4 flex items-center justify-between">
            <div>
              <h2 className="text-sm font-semibold">Equity Curve</h2>
              <p className="text-xs text-muted-foreground">Account balance over each trade</p>
            </div>
            <TrendingUp className="h-4 w-4 text-muted-foreground" />
          </div>
          <div className="h-72">
            <ResponsiveContainer width="100%" height="100%">
              <LineChart data={equityData} margin={{ top: 5, right: 10, left: 0, bottom: 5 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="hsl(var(--border))" opacity={0.4} />
                <XAxis dataKey="name" tick={{ fontSize: 11, fill: 'hsl(var(--muted-foreground))' }} />
                <YAxis
                  tick={{ fontSize: 11, fill: 'hsl(var(--muted-foreground))' }}
                  tickFormatter={(v) => `$${(v / 1000).toFixed(1)}k`}
                  width={50}
                />
                <Tooltip
                  contentStyle={{
                    background: 'hsl(var(--popover))',
                    border: '1px solid hsl(var(--border))',
                    borderRadius: 8,
                    fontSize: 12,
                  }}
                  formatter={(v: number) => [fmtMoney(v), 'Balance']}
                />
                <ReferenceLine y={settings.starting_balance} stroke="hsl(var(--muted-foreground))" strokeDasharray="4 4" />
                <Line
                  type="monotone"
                  dataKey="balance"
                  stroke="hsl(var(--chart-1))"
                  strokeWidth={2}
                  dot={false}
                  activeDot={{ r: 4 }}
                />
              </LineChart>
            </ResponsiveContainer>
          </div>
        </Card>

        <Card className="p-5">
          <div className="mb-4 flex items-center justify-between">
            <div>
              <h2 className="text-sm font-semibold">Net P&amp;L by Strategy</h2>
              <p className="text-xs text-muted-foreground">Total dollar return per strategy</p>
            </div>
            <Activity className="h-4 w-4 text-muted-foreground" />
          </div>
          {strategyData.length === 0 ? (
            <EmptyChart label="No trades yet" />
          ) : (
            <div className="h-72">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={strategyData} layout="vertical" margin={{ top: 0, right: 10, left: 10, bottom: 0 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke="hsl(var(--border))" opacity={0.4} horizontal={false} />
                  <XAxis type="number" tick={{ fontSize: 11, fill: 'hsl(var(--muted-foreground))' }} tickFormatter={(v) => `$${v}`} />
                  <YAxis type="category" dataKey="name" tick={{ fontSize: 11, fill: 'hsl(var(--muted-foreground))' }} width={90} />
                  <Tooltip
                    contentStyle={{
                      background: 'hsl(var(--popover))',
                      border: '1px solid hsl(var(--border))',
                      borderRadius: 8,
                      fontSize: 12,
                    }}
                    formatter={(v: number) => [fmtMoney(v, { sign: true }), 'Net P&L']}
                  />
                  <ReferenceLine x={0} stroke="hsl(var(--border))" />
                  <Bar dataKey="net" radius={[0, 4, 4, 0]}>
                    {strategyData.map((entry, i) => (
                      <Cell key={i} fill={entry.net >= 0 ? 'hsl(142 71% 45%)' : 'hsl(0 72% 51%)'} />
                    ))}
                  </Bar>
                </BarChart>
              </ResponsiveContainer>
            </div>
          )}
        </Card>
      </div>

      <SettingsEditor
        settings={settings}
        strategies={strategies}
        onSave={saveSettings}
        onAddStrategy={addStrategy}
        onDeleteStrategy={deleteStrategy}
      />
    </div>
  );
}

function StatGrid({ stats }: { stats: ReturnType<typeof computeStats> }) {
  const cards = [
    { label: 'Total Trades', value: String(stats.totalTrades), icon: Activity, accent: 'text-blue-500' },
    { label: 'Win Rate', value: stats.totalTrades ? fmtPct(stats.winRate) : '—', icon: Target, accent: 'text-emerald-500' },
    { label: 'Total Net P&L', value: fmtMoney(stats.totalNetPnl, { sign: true }), icon: stats.totalNetPnl >= 0 ? TrendingUp : TrendingDown, accent: moneyColor(stats.totalNetPnl) },
    { label: 'Current Balance', value: fmtMoney(stats.currentBalance), icon: Wallet, accent: 'text-foreground' },
    { label: 'Avg Win', value: fmtMoney(stats.averageWin), icon: TrendingUp, accent: 'text-emerald-500' },
    { label: 'Avg Loss', value: fmtMoney(stats.averageLoss), icon: TrendingDown, accent: 'text-red-500' },
    { label: 'Largest Win', value: fmtMoney(stats.largestWin), icon: TrendingUp, accent: 'text-emerald-500' },
    { label: 'Largest Loss', value: fmtMoney(stats.largestLoss), icon: TrendingDown, accent: 'text-red-500' },
    {
      label: 'Profit Factor',
      value: stats.profitFactor === Infinity ? '∞' : fmtNum(stats.profitFactor, 2),
      icon: Scale,
      accent: stats.profitFactor >= 1 ? 'text-emerald-500' : 'text-red-500',
    },
    { label: 'Avg R Multiple', value: stats.averageRMultiple != null ? `${stats.averageRMultiple >= 0 ? '+' : ''}${fmtNum(stats.averageRMultiple)}` : '—', icon: Gauge, accent: moneyColor(stats.averageRMultiple ?? 0) },
    { label: 'Avg Risk / Trade', value: stats.averageRiskPercent != null ? fmtPct(stats.averageRiskPercent) : '—', icon: Percent, accent: 'text-foreground' },
  ];

  return (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
      {cards.map((c) => {
        const Icon = c.icon;
        return (
          <Card key={c.label} className="p-4">
            <div className="flex items-center justify-between">
              <span className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">{c.label}</span>
              <Icon className={`h-3.5 w-3.5 ${c.accent}`} />
            </div>
            <div className={`mt-2 text-lg font-semibold tabular-nums ${c.accent}`}>{c.value}</div>
          </Card>
        );
      })}
    </div>
  );
}

function EmptyChart({ label }: { label: string }) {
  return (
    <div className="flex h-72 items-center justify-center text-sm text-muted-foreground">{label}</div>
  );
}

function SettingsEditor({
  settings,
  strategies,
  onSave,
  onAddStrategy,
  onDeleteStrategy,
}: {
  settings: Journal['settings'];
  strategies: Journal['strategies'];
  onSave: (patch: Partial<Journal['settings']>) => Promise<void>;
  onAddStrategy: (name: string) => Promise<void>;
  onDeleteStrategy: (id: string) => Promise<void>;
}) {
  const [form, setForm] = useState({
    starting_balance: String(settings.starting_balance),
    pip_size: String(settings.pip_size),
    pip_value_per_lot: String(settings.pip_value_per_lot),
    risk_warning_threshold: String(settings.risk_warning_threshold),
  });
  const [newStrategy, setNewStrategy] = useState('');
  const [saving, setSaving] = useState(false);
  const [savedFlash, setSavedFlash] = useState(false);

  const handleSave = async () => {
    setSaving(true);
    try {
      await onSave({
        starting_balance: Number(form.starting_balance) || 0,
        pip_size: Number(form.pip_size) || 0.01,
        pip_value_per_lot: Number(form.pip_value_per_lot) || 1,
        risk_warning_threshold: Number(form.risk_warning_threshold) || 2,
      });
      setSavedFlash(true);
      setTimeout(() => setSavedFlash(false), 1500);
    } finally {
      setSaving(false);
    }
  };

  const handleAddStrategy = async () => {
    const name = newStrategy.trim();
    if (!name) return;
    try {
      await onAddStrategy(name);
      setNewStrategy('');
    } catch {
      // duplicate name etc — silently ignore; list stays correct
    }
  };

  return (
    <Card className="p-5">
      <div className="mb-4">
        <h2 className="text-sm font-semibold">Settings &amp; Strategies</h2>
        <p className="text-xs text-muted-foreground">These values drive every calculated field in your journal.</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <SettingField label="Starting Balance">
          <Input type="number" step="any" value={form.starting_balance} onChange={(e) => setForm({ ...form, starting_balance: e.target.value })} />
        </SettingField>
        <SettingField label="Pip Size">
          <Input type="number" step="any" value={form.pip_size} onChange={(e) => setForm({ ...form, pip_size: e.target.value })} />
        </SettingField>
        <SettingField label="Pip Value per Lot ($)">
          <Input type="number" step="any" value={form.pip_value_per_lot} onChange={(e) => setForm({ ...form, pip_value_per_lot: e.target.value })} />
        </SettingField>
        <SettingField label="Risk Warning Threshold (%)">
          <Input type="number" step="any" value={form.risk_warning_threshold} onChange={(e) => setForm({ ...form, risk_warning_threshold: e.target.value })} />
        </SettingField>
      </div>

      <div className="mt-4 flex items-center gap-3">
        <Button size="sm" onClick={handleSave} disabled={saving}>
          <Save className="mr-1.5 h-3.5 w-3.5" />
          {saving ? 'Saving…' : savedFlash ? 'Saved!' : 'Save Settings'}
        </Button>
        <span className="text-xs text-muted-foreground">Trades flagged when risk exceeds {form.risk_warning_threshold || 2}% of balance.</span>
      </div>

      <div className="mt-6 border-t border-border pt-5">
        <div className="mb-3 flex items-center justify-between">
          <h3 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Strategies</h3>
        </div>
        <div className="flex flex-wrap gap-2">
          {strategies.map((s) => (
            <span
              key={s.id}
              className="group inline-flex items-center gap-1.5 rounded-full border border-border bg-muted/50 py-1 pl-3 pr-1.5 text-xs font-medium"
            >
              {s.name}
              <button
                onClick={() => onDeleteStrategy(s.id)}
                className="flex h-4 w-4 items-center justify-center rounded-full text-muted-foreground opacity-0 transition-opacity hover:bg-destructive/15 hover:text-destructive group-hover:opacity-100"
                aria-label={`Delete ${s.name}`}
              >
                <Trash2 className="h-3 w-3" />
              </button>
            </span>
          ))}
        </div>
        <div className="mt-3 flex items-center gap-2">
          <Input
            value={newStrategy}
            onChange={(e) => setNewStrategy(e.target.value)}
            placeholder="Add a custom strategy…"
            className="max-w-xs"
            onKeyDown={(e) => e.key === 'Enter' && handleAddStrategy()}
          />
          <Button size="sm" variant="outline" onClick={handleAddStrategy} disabled={!newStrategy.trim()}>
            <Plus className="mr-1 h-3.5 w-3.5" /> Add
          </Button>
        </div>
      </div>
    </Card>
  );
}

function SettingField({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div className="space-y-1.5">
      <Label className="text-xs font-medium text-muted-foreground">{label}</Label>
      {children}
    </div>
  );
}
