'use client';

import { useCallback, useEffect, useMemo, useState } from 'react';
import type { User } from '@supabase/supabase-js';
import { getSupabaseClient, isSupabaseConfigured } from '@/lib/supabase';
import { invokeJournalApi } from '@/lib/edge-api';
import { calcTrades } from '@/lib/calc';
import { getAuthRedirectUrl } from '@/lib/auth-redirect';
import { DEMO_SETTINGS, DEMO_STRATEGIES, DEMO_TRADES } from '@/lib/demo-data';
import type { Profile, Settings, Strategy, Trade, TradeWithCalc } from '@/lib/types';

const DEFAULT_SETTINGS: Settings = {
  id: '',
  starting_balance: 10000,
  pip_size: 0.01,
  pip_value_per_lot: 1,
  risk_warning_threshold: 2,
  idle_timeout_minutes: 30,
  updated_at: '',
};

const DEFAULT_STRATEGIES = [
  'Breakout', 'Reversal', 'Trend Following', 'Mean Reversion', 'Momentum',
  'Scalp', 'Swing', 'News Play', 'Unreviewed', 'Other',
];

export function useJournalData() {
  const demoMode = !isSupabaseConfigured();
  const [trades, setTrades] = useState<Trade[]>([]);
  const [settings, setSettings] = useState<Settings>(DEFAULT_SETTINGS);
  const [strategies, setStrategies] = useState<Strategy[]>([]);
  const [profile, setProfile] = useState<Profile | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [user, setUser] = useState<User | null>(null);
  const [authReady, setAuthReady] = useState(demoMode);

  useEffect(() => {
    if (demoMode) return;
    const supabase = getSupabaseClient();
    let active = true;
    supabase.auth.getSession()
      .then(({ data }) => {
        if (active) {
          setUser(data.session?.user ?? null);
          setAuthReady(true);
        }
      })
      .catch((sessionError) => {
        if (active) {
          setError(sessionError instanceof Error ? sessionError.message : 'Could not restore your session.');
          setAuthReady(true);
          setLoading(false);
        }
      });
    const { data } = supabase.auth.onAuthStateChange((_event, session) => {
      setUser(session?.user ?? null);
      setAuthReady(true);
    });
    return () => {
      active = false;
      data.subscription.unsubscribe();
    };
  }, [demoMode]);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    if (demoMode) {
      setSettings({ ...DEMO_SETTINGS });
      setStrategies(DEMO_STRATEGIES.map((strategy) => ({ ...strategy })));
      setTrades(DEMO_TRADES.map((trade) => ({ ...trade })));
      setLoading(false);
      return;
    }
    if (!user) {
      setTrades([]);
      setStrategies([]);
      setProfile(null);
      setLoading(false);
      return;
    }
    try {
      const supabase = getSupabaseClient();
      const { data: profileData, error: profileError } = await supabase
        .from('profiles')
        .select('id,email,username,display_name,avatar_url,created_at,updated_at')
        .maybeSingle();
      if (profileError) throw profileError;
      setProfile((profileData as Profile | null) ?? null);

      let { data: settingsData, error: settingsErr } = await supabase
        .from('settings')
        .select('*')
        .maybeSingle();

      if (settingsErr) throw settingsErr;

      if (!settingsData) {
        const { data: created, error: createErr } = await supabase
          .from('settings')
          .insert({
            starting_balance: 10000,
            pip_size: 0.01,
            pip_value_per_lot: 1,
            risk_warning_threshold: 2,
          })
          .select()
          .maybeSingle();
        if (createErr) throw createErr;
        settingsData = created;
      }
      setSettings(settingsData as Settings);

      let { data: stratData, error: stratErr } = await supabase
        .from('strategies')
        .select('*')
        .order('name');
      if (stratErr) throw stratErr;
      if (!stratData?.length) {
        const { data: createdStrategies, error: createStrategiesError } = await supabase
          .from('strategies')
          .insert(DEFAULT_STRATEGIES.map((name) => ({ name })))
          .select('*');
        if (createStrategiesError) throw createStrategiesError;
        stratData = createdStrategies;
      }
      setStrategies((stratData as Strategy[]) ?? []);

      const { data: tradeData, error: tradeErr } = await supabase
        .from('trades')
        .select('*')
        .order('date', { ascending: true });
      if (tradeErr) throw tradeErr;
      setTrades((tradeData as Trade[]) ?? []);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Failed to load journal data.');
    } finally {
      setLoading(false);
    }
  }, [demoMode, user]);

  useEffect(() => {
    if (demoMode || authReady) load();
  }, [authReady, demoMode, load]);

  const tradesWithCalc: TradeWithCalc[] = useMemo(
    () => calcTrades(trades, settings),
    [trades, settings]
  );

  const refreshStrategies = useCallback(async () => {
    if (demoMode) return;
    const supabase = getSupabaseClient();
    const { data, error: err } = await supabase.from('strategies').select('*').order('name');
    if (err) return;
    setStrategies((data as Strategy[]) ?? []);
  }, [demoMode]);

  const saveSettings = useCallback(async (patch: Partial<Settings>) => {
    if (demoMode) {
      setSettings((current) => ({
        ...current,
        ...patch,
        updated_at: new Date().toISOString(),
      }));
      return;
    }
    const supabase = getSupabaseClient();
    const { data, error: err } = await supabase
      .from('settings')
      .update({ ...patch, updated_at: new Date().toISOString() })
      .eq('id', settings.id)
      .select()
      .maybeSingle();
    if (err) throw err;
    if (data) setSettings(data as Settings);
  }, [demoMode, settings.id]);

  const addTrade = useCallback(async (trade: Omit<Trade, 'id' | 'created_at' | 'updated_at'>) => {
    if (demoMode) {
      const timestamp = new Date().toISOString();
      const created = { ...trade, id: `demo-trade-${Date.now()}`, created_at: timestamp, updated_at: timestamp } as Trade;
      setTrades((current) => [
        ...current,
        created,
      ]);
      return created;
    }
    const supabase = getSupabaseClient();
    const { data, error: err } = await supabase
      .from('trades')
      .insert(trade)
      .select()
      .maybeSingle();
    if (err) throw err;
    if (data) {
      setTrades((prev) => [...prev, data as Trade]);
      return data as Trade;
    }
    throw new Error('The trade was saved, but no database record was returned.');
  }, [demoMode]);

  const updateTrade = useCallback(async (id: string, patch: Partial<Trade>) => {
    if (demoMode) {
      setTrades((current) =>
        current.map((trade) =>
          trade.id === id
            ? { ...trade, ...patch, updated_at: new Date().toISOString() }
            : trade
        )
      );
      return;
    }
    const supabase = getSupabaseClient();
    const { data, error: err } = await supabase
      .from('trades')
      .update({ ...patch, updated_at: new Date().toISOString() })
      .eq('id', id)
      .select()
      .maybeSingle();
    if (err) throw err;
    if (data) {
      setTrades((prev) => prev.map((t) => (t.id === id ? (data as Trade) : t)));
    }
  }, [demoMode]);

  const deleteTrade = useCallback(async (id: string) => {
    if (demoMode) {
      setTrades((current) => current.filter((trade) => trade.id !== id));
      return;
    }
    const supabase = getSupabaseClient();
    const { error: err } = await supabase.from('trades').delete().eq('id', id);
    if (err) throw err;
    setTrades((prev) => prev.filter((t) => t.id !== id));
  }, [demoMode]);

  const addStrategy = useCallback(async (name: string) => {
    if (demoMode) {
      setStrategies((current) => [
        ...current,
        { id: `demo-strategy-${Date.now()}`, name, created_at: new Date().toISOString() },
      ]);
      return;
    }
    const supabase = getSupabaseClient();
    const { data, error: err } = await supabase
      .from('strategies')
      .insert({ name })
      .select()
      .maybeSingle();
    if (err) throw err;
    if (data) await refreshStrategies();
  }, [demoMode, refreshStrategies]);

  const deleteStrategy = useCallback(async (id: string) => {
    if (demoMode) {
      setStrategies((current) => current.filter((strategy) => strategy.id !== id));
      return;
    }
    const supabase = getSupabaseClient();
    const { error: err } = await supabase.from('strategies').delete().eq('id', id);
    if (err) throw err;
    await refreshStrategies();
  }, [demoMode, refreshStrategies]);

  const signInWithPassword = useCallback(async (email: string, password: string) => {
    const { error: signInError } = await getSupabaseClient().auth.signInWithPassword({ email, password });
    if (signInError) throw signInError;
  }, []);

  const signUpWithPassword = useCallback(async (name: string, email: string, password: string) => {
    const requireEmailConfirmation = process.env.NEXT_PUBLIC_REQUIRE_EMAIL_CONFIRMATION === 'true';

    // Keep the normal confirmation flow ready for when email verification is restored.
    if (requireEmailConfirmation) {
      const { data, error: signUpError } = await getSupabaseClient().auth.signUp({
        email,
        password,
        options: {
          emailRedirectTo: getAuthRedirectUrl(),
          data: { full_name: name.trim() },
        },
      });
      if (signUpError) throw signUpError;
      return { requiresEmailConfirmation: !data.session };
    }

    await invokeJournalApi({ action: 'register', name, email, password });
    return { requiresEmailConfirmation: false };
  }, []);

  const signInWithGoogle = useCallback(async () => {
    const { error: signInError } = await getSupabaseClient().auth.signInWithOAuth({
      provider: 'google',
      options: { redirectTo: getAuthRedirectUrl() },
    });
    if (signInError) throw signInError;
  }, []);

  const signOut = useCallback(async () => {
    const { error: signOutError } = await getSupabaseClient().auth.signOut({ scope: 'local' });
    if (signOutError) throw signOutError;
  }, []);

  return {
    trades,
    tradesWithCalc,
    settings,
    strategies,
    profile,
    demoMode,
    user,
    authReady,
    loading,
    error,
    reload: load,
    saveSettings,
    addTrade,
    updateTrade,
    deleteTrade,
    addStrategy,
    deleteStrategy,
    signInWithPassword,
    signUpWithPassword,
    signInWithGoogle,
    signOut,
  };
}
