/*
# Trading Journal — initial schema

1. New Tables
- `strategies`: user-managed list of trading strategy names (Breakout, Reversal, etc.).
  - `id` (uuid PK)
  - `name` (text, unique, not null)
  - `created_at` (timestamptz)
- `settings`: single-row per-journal configuration (starting balance, pip settings, risk threshold).
  - `id` (uuid PK)
  - `starting_balance` (numeric, default 10000)
  - `pip_size` (numeric, default 0.01)
  - `pip_value_per_lot` (numeric, default 1.00)
  - `risk_warning_threshold` (numeric percent, default 2)
  - `updated_at` (timestamptz)
- `trades`: the core trade log.
  - `id` (uuid PK)
  - `trade_number` (integer, manual/auto sequence)
  - `date` (date, when the trade was closed)
  - `symbol` (text, not null)
  - `direction` (text: 'Long' | 'Short', not null)
  - `strategy` (text, not null — stored as string for flexibility)
  - `calc_mode` (text: 'pips' | 'shares', default 'pips')
  - `entry_price` (numeric, not null)
  - `exit_price` (numeric, not null)
  - `lot_size` (numeric, not null — lot size for pips, shares for shares mode)
  - `fees` (numeric, default 0)
  - `stop_loss` (numeric)
  - `target_price` (numeric)
  - `setup_notes` (text)
  - `lessons_learned` (text)
  - `created_at`, `updated_at` (timestamptz)
  NOTE: All calculated fields (risk, reward, p&l, r-multiple, balance, win/loss)
  are DERIVED client-side from the formulas — never stored — so numbers never drift.

2. Security
- Enable RLS on all three tables.
- Single-user v1 (no sign-in screen): policies allow anon + authenticated full CRUD
  so the anon-key frontend can operate. Schema is designed to add user_id later.

3. Important Notes
- Calculated fields are not persisted; see the calc engine in lib/calc.ts.
- `strategy` is a free text column but constrained in the UI to the strategies table.
- A trigger is NOT added for trade_number; the client assigns it as max+1 on insert.
- A trigger seeds a default settings row and default strategies when none exist is
  handled client-side on first load to keep the migration pure-schema.
*/

CREATE TABLE IF NOT EXISTS strategies (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  name text UNIQUE NOT NULL,
  created_at timestamptz DEFAULT now()
);

CREATE TABLE IF NOT EXISTS settings (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  starting_balance numeric NOT NULL DEFAULT 10000,
  pip_size numeric NOT NULL DEFAULT 0.01,
  pip_value_per_lot numeric NOT NULL DEFAULT 1.00,
  risk_warning_threshold numeric NOT NULL DEFAULT 2,
  updated_at timestamptz DEFAULT now()
);

CREATE TABLE IF NOT EXISTS trades (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  trade_number integer NOT NULL,
  date date NOT NULL,
  symbol text NOT NULL,
  direction text NOT NULL CHECK (direction IN ('Long','Short')),
  strategy text NOT NULL,
  calc_mode text NOT NULL DEFAULT 'pips' CHECK (calc_mode IN ('pips','shares')),
  entry_price numeric NOT NULL,
  exit_price numeric NOT NULL,
  lot_size numeric NOT NULL,
  fees numeric NOT NULL DEFAULT 0,
  stop_loss numeric,
  target_price numeric,
  setup_notes text,
  lessons_learned text,
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now()
);

ALTER TABLE strategies ENABLE ROW LEVEL SECURITY;
ALTER TABLE settings ENABLE ROW LEVEL SECURITY;
ALTER TABLE trades ENABLE ROW LEVEL SECURITY;

-- strategies policies
DROP POLICY IF EXISTS "anon_select_strategies" ON strategies;
CREATE POLICY "anon_select_strategies" ON strategies FOR SELECT
  TO anon, authenticated USING (true);

DROP POLICY IF EXISTS "anon_insert_strategies" ON strategies;
CREATE POLICY "anon_insert_strategies" ON strategies FOR INSERT
  TO anon, authenticated WITH CHECK (true);

DROP POLICY IF EXISTS "anon_update_strategies" ON strategies;
CREATE POLICY "anon_update_strategies" ON strategies FOR UPDATE
  TO anon, authenticated USING (true) WITH CHECK (true);

DROP POLICY IF EXISTS "anon_delete_strategies" ON strategies;
CREATE POLICY "anon_delete_strategies" ON strategies FOR DELETE
  TO anon, authenticated USING (true);

-- settings policies
DROP POLICY IF EXISTS "anon_select_settings" ON settings;
CREATE POLICY "anon_select_settings" ON settings FOR SELECT
  TO anon, authenticated USING (true);

DROP POLICY IF EXISTS "anon_insert_settings" ON settings;
CREATE POLICY "anon_insert_settings" ON settings FOR INSERT
  TO anon, authenticated WITH CHECK (true);

DROP POLICY IF EXISTS "anon_update_settings" ON settings;
CREATE POLICY "anon_update_settings" ON settings FOR UPDATE
  TO anon, authenticated USING (true) WITH CHECK (true);

DROP POLICY IF EXISTS "anon_delete_settings" ON settings;
CREATE POLICY "anon_delete_settings" ON settings FOR DELETE
  TO anon, authenticated USING (true);

-- trades policies
DROP POLICY IF EXISTS "anon_select_trades" ON trades;
CREATE POLICY "anon_select_trades" ON trades FOR SELECT
  TO anon, authenticated USING (true);

DROP POLICY IF EXISTS "anon_insert_trades" ON trades;
CREATE POLICY "anon_insert_trades" ON trades FOR INSERT
  TO anon, authenticated WITH CHECK (true);

DROP POLICY IF EXISTS "anon_update_trades" ON trades;
CREATE POLICY "anon_update_trades" ON trades FOR UPDATE
  TO anon, authenticated USING (true) WITH CHECK (true);

DROP POLICY IF EXISTS "anon_delete_trades" ON trades;
CREATE POLICY "anon_delete_trades" ON trades FOR DELETE
  TO anon, authenticated USING (true);

-- Seed default strategies (idempotent)
INSERT INTO strategies (name) VALUES
  ('Breakout'), ('Reversal'), ('Trend Following'), ('Mean Reversion'),
  ('Momentum'), ('Scalp'), ('Swing'), ('News Play'), ('Other')
ON CONFLICT (name) DO NOTHING;

-- Seed one default settings row if none exists
INSERT INTO settings (starting_balance, pip_size, pip_value_per_lot, risk_warning_threshold)
SELECT 10000, 0.01, 1.00, 2
WHERE NOT EXISTS (SELECT 1 FROM settings);
