'use client';

import { useEffect, useState } from 'react';
import { AlertTriangle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { validateStopLoss, nextTradeNumber } from '@/lib/calc';
import {
  GENERIC_TRADE_EMOTIONS,
  MAX_TRADE_EMOTIONS,
  MAX_TRADE_EMOTION_LENGTH,
  normalizeTradeEmotions,
  toggleTradeEmotion,
} from '@/lib/trade-emotions';
import { cn } from '@/lib/utils';
import type { CalcMode, Direction, Strategy, Trade } from '@/lib/types';

export interface TradeFormValues {
  trade_number: number;
  date: string;
  symbol: string;
  direction: Direction;
  strategy: string;
  calc_mode: CalcMode;
  entry_price: number;
  exit_price: number;
  lot_size: number;
  fees: number;
  stop_loss: number | null;
  target_price: number | null;
  setup_notes: string | null;
  lessons_learned: string | null;
  exit_reason: string | null;
  emotion_during_trade: string | null;
  emotions: string[];
}

interface Props {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  strategies: Strategy[];
  trades: Trade[];
  editing?: Trade | null;
  onSave: (values: TradeFormValues) => Promise<void>;
}

const EMPTY: TradeFormValues = {
  trade_number: 1,
  date: new Date().toISOString().slice(0, 10),
  symbol: '',
  direction: 'Long',
  strategy: 'Breakout',
  calc_mode: 'pips',
  entry_price: 0,
  exit_price: 0,
  lot_size: 1,
  fees: 0,
  stop_loss: null,
  target_price: null,
  setup_notes: null,
  lessons_learned: null,
  exit_reason: null,
  emotion_during_trade: null,
  emotions: [],
};

export function TradeForm({ open, onOpenChange, strategies, trades, editing, onSave }: Props) {
  const [values, setValues] = useState<TradeFormValues>(EMPTY);
  const [saving, setSaving] = useState(false);
  const [stopWarn, setStopWarn] = useState(false);
  const [customEmotion, setCustomEmotion] = useState('');

  useEffect(() => {
    if (editing) {
      setValues({
        trade_number: editing.trade_number,
        date: editing.date,
        symbol: editing.symbol,
        direction: editing.direction,
        strategy: editing.strategy,
        calc_mode: editing.calc_mode,
        entry_price: editing.entry_price,
        exit_price: editing.exit_price,
        lot_size: editing.lot_size,
        fees: editing.fees,
        stop_loss: editing.stop_loss,
        target_price: editing.target_price,
        setup_notes: editing.setup_notes,
        lessons_learned: editing.lessons_learned,
        exit_reason: editing.exit_reason ?? null,
        emotion_during_trade: editing.emotion_during_trade ?? null,
        emotions: editing.emotions ?? [],
      });
    } else {
      setValues({ ...EMPTY, trade_number: nextTradeNumber(trades), date: new Date().toISOString().slice(0, 10) });
    }
    setStopWarn(false);
    setCustomEmotion('');
  }, [editing, trades, open]);

  useEffect(() => {
    if (values.stop_loss != null && values.entry_price > 0) {
      setStopWarn(!validateStopLoss(values.direction, values.entry_price, values.stop_loss));
    } else {
      setStopWarn(false);
    }
  }, [values.stop_loss, values.entry_price, values.direction]);

  const update = <K extends keyof TradeFormValues>(key: K, val: TradeFormValues[K]) =>
    setValues((v) => ({ ...v, [key]: val }));

  const numOrEmpty = (n: number | null) => (n == null ? '' : String(n));
  const parseNum = (s: string): number | null => {
    if (s.trim() === '') return null;
    const n = Number(s);
    return Number.isFinite(n) ? n : null;
  };

  const handleSubmit = async () => {
    if (!values.symbol.trim()) return;
    const clean: TradeFormValues = {
      ...values,
      symbol: values.symbol.trim().toUpperCase(),
      entry_price: Number(values.entry_price) || 0,
      exit_price: Number(values.exit_price) || 0,
      lot_size: Number(values.lot_size) || 0,
      fees: Number(values.fees) || 0,
      emotions: normalizeTradeEmotions(values.emotions),
    };
    setSaving(true);
    try {
      await onSave(clean);
      onOpenChange(false);
    } finally {
      setSaving(false);
    }
  };

  const emotionOptions = [...GENERIC_TRADE_EMOTIONS, ...values.emotions].filter(
    (emotion, index, all) =>
      all.findIndex((value) => value.toLocaleLowerCase() === emotion.toLocaleLowerCase()) === index
  );
  const addCustomEmotion = () => {
    if (!customEmotion.trim()) return;
    const alreadySelected = values.emotions.some(
      (emotion) => emotion.toLocaleLowerCase() === customEmotion.trim().toLocaleLowerCase()
    );
    if (!alreadySelected) {
      update('emotions', normalizeTradeEmotions([...values.emotions, customEmotion]));
    }
    setCustomEmotion('');
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[92vh] overflow-y-auto sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>{editing ? `Edit Trade #${editing.trade_number}` : 'Add Trade'}</DialogTitle>
          <DialogDescription>
            Enter the trade details. Risk, P&amp;L, R multiple and account balance are calculated automatically.
          </DialogDescription>
        </DialogHeader>

        <div className="grid gap-4 py-2 sm:grid-cols-2 lg:grid-cols-3">
          <Field label="Date">
            <Input type="date" value={values.date} onChange={(e) => update('date', e.target.value)} />
          </Field>
          <Field label="Symbol">
            <Input value={values.symbol} onChange={(e) => update('symbol', e.target.value)} placeholder="XAUUSD" />
          </Field>
          <Field label="Trade #">
            <Input
              type="number"
              value={values.trade_number}
              onChange={(e) => update('trade_number', Number(e.target.value) || 1)}
            />
          </Field>

          <Field label="Direction">
            <Select value={values.direction} onValueChange={(v) => update('direction', v as Direction)}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="Long">Long</SelectItem>
                <SelectItem value="Short">Short</SelectItem>
              </SelectContent>
            </Select>
          </Field>

          <Field label="Strategy">
            <Select value={values.strategy} onValueChange={(v) => update('strategy', v)}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                {strategies.map((s) => (
                  <SelectItem key={s.id} value={s.name}>{s.name}</SelectItem>
                ))}
              </SelectContent>
            </Select>
          </Field>

          <Field label="Calc Mode">
            <Select value={values.calc_mode} onValueChange={(v) => update('calc_mode', v as CalcMode)}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="pips">Pips (Forex/Gold)</SelectItem>
                <SelectItem value="shares">Shares (Stocks/Crypto)</SelectItem>
              </SelectContent>
            </Select>
          </Field>

          <Field label="Entry Price">
            <Input
              type="number"
              step="any"
              value={numOrEmpty(values.entry_price)}
              onChange={(e) => update('entry_price', parseNum(e.target.value) ?? 0)}
            />
          </Field>
          <Field label="Exit Price">
            <Input
              type="number"
              step="any"
              value={numOrEmpty(values.exit_price)}
              onChange={(e) => update('exit_price', parseNum(e.target.value) ?? 0)}
            />
          </Field>
          <Field label={values.calc_mode === 'pips' ? 'Lot Size' : 'Shares'}>
            <Input
              type="number"
              step="any"
              value={numOrEmpty(values.lot_size)}
              onChange={(e) => update('lot_size', parseNum(e.target.value) ?? 0)}
            />
          </Field>

          <Field label="Stop Loss">
            <Input
              type="number"
              step="any"
              value={numOrEmpty(values.stop_loss)}
              onChange={(e) => update('stop_loss', parseNum(e.target.value))}
              placeholder="Optional"
            />
          </Field>
          <Field label="Target Price">
            <Input
              type="number"
              step="any"
              value={numOrEmpty(values.target_price)}
              onChange={(e) => update('target_price', parseNum(e.target.value))}
              placeholder="Optional"
            />
          </Field>
          <Field label="Fees">
            <Input
              type="number"
              step="any"
              value={numOrEmpty(values.fees)}
              onChange={(e) => update('fees', parseNum(e.target.value) ?? 0)}
            />
          </Field>

          <div className="sm:col-span-2 lg:col-span-3">
            <Field label="Emotions Involved in This Trade">
              <div className="space-y-3 rounded-md border border-border/70 bg-muted/20 p-3">
                <div className="flex flex-wrap gap-2">
                  {emotionOptions.map((emotion) => {
                    const selected = values.emotions.some(
                      (value) => value.toLocaleLowerCase() === emotion.toLocaleLowerCase()
                    );
                    return (
                      <button
                        key={emotion}
                        type="button"
                        aria-pressed={selected}
                        onClick={() => update('emotions', toggleTradeEmotion(values.emotions, emotion))}
                        className={cn(
                          'rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                          selected
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border bg-background text-muted-foreground hover:border-primary/50 hover:text-foreground'
                        )}
                      >
                        {emotion}
                      </button>
                    );
                  })}
                </div>
                <div className="flex gap-2">
                  <Input
                    value={customEmotion}
                    maxLength={MAX_TRADE_EMOTION_LENGTH}
                    onChange={(event) => setCustomEmotion(event.target.value)}
                    onKeyDown={(event) => {
                      if (event.key === 'Enter') {
                        event.preventDefault();
                        addCustomEmotion();
                      }
                    }}
                    placeholder="Add another emotion"
                    aria-label="Custom trade emotion"
                  />
                  <Button
                    type="button"
                    variant="outline"
                    onClick={addCustomEmotion}
                    disabled={!customEmotion.trim() || values.emotions.length >= MAX_TRADE_EMOTIONS}
                  >
                    Add
                  </Button>
                </div>
                <p className="text-xs text-muted-foreground">
                  Select every emotion that influenced this trade ({values.emotions.length}/{MAX_TRADE_EMOTIONS}).
                </p>
              </div>
            </Field>
          </div>

          <div className="sm:col-span-2 lg:col-span-3">
            <Field label="Why I Entered">
              <Textarea
                rows={3}
                value={values.setup_notes ?? ''}
                onChange={(e) => update('setup_notes', e.target.value || null)}
                placeholder="Describe the setup, confirmation, and reason for entering."
              />
            </Field>
          </div>
          <div className="sm:col-span-2 lg:col-span-3">
            <Field label="Why I Exited">
              <Textarea
                rows={3}
                value={values.exit_reason ?? ''}
                onChange={(e) => update('exit_reason', e.target.value || null)}
                placeholder="Why did you close the trade? Target, stop, signal change, or discretion?"
              />
            </Field>
          </div>
          <div className="sm:col-span-2 lg:col-span-3">
            <Field label="How I Felt During the Trade">
              <Textarea
                rows={3}
                value={values.emotion_during_trade ?? ''}
                onChange={(e) => update('emotion_during_trade', e.target.value || null)}
                placeholder="How did your emotions change while the position was open?"
              />
            </Field>
          </div>
          <div className="sm:col-span-2 lg:col-span-3">
            <Field label="Lessons Learned">
              <Textarea
                rows={2}
                value={values.lessons_learned ?? ''}
                onChange={(e) => update('lessons_learned', e.target.value || null)}
                placeholder="What did this trade teach you?"
              />
            </Field>
          </div>
        </div>

        {stopWarn && (
          <div className="flex items-start gap-2 rounded-md border border-amber-500/50 bg-amber-500/10 p-3 text-sm text-amber-700 dark:text-amber-400">
            <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" />
            <span>
              Stop loss is on the wrong side of entry for a {values.direction.toLowerCase()} trade. A long stop
              should be below entry; a short stop should be above entry. You can still save — this is a warning.
            </span>
          </div>
        )}

        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)} disabled={saving}>Cancel</Button>
          <Button onClick={handleSubmit} disabled={saving || !values.symbol.trim()}>
            {saving ? 'Saving…' : editing ? 'Save Changes' : 'Add Trade'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div className="space-y-1.5">
      <Label className="text-xs font-medium text-muted-foreground">{label}</Label>
      {children}
    </div>
  );
}
