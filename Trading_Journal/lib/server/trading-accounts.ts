import { getSupabaseAdminClient } from './supabase-admin';

export async function getOwnedTradingAccount(id: string, userId: string) {
  const { data, error } = await getSupabaseAdminClient()
    .from('trading_accounts')
    .select('*')
    .eq('id', id)
    .eq('user_id', userId)
    .maybeSingle();
  if (error) throw error;
  return data;
}

export function maskAccountNumber(accountNumber: string) {
  if (accountNumber.length <= 4) return '*'.repeat(accountNumber.length);
  return `${'*'.repeat(Math.min(8, accountNumber.length - 4))}${accountNumber.slice(-4)}`;
}

export function publicTradingAccount<
  T extends {
    account_number: string;
    connector_token_hash?: unknown;
    pairing_code_hash?: unknown;
    broker_password_encrypted?: unknown;
  }
>(account: T) {
  const { connector_token_hash, pairing_code_hash, broker_password_encrypted, ...safeAccount } = account;
  void connector_token_hash;
  void pairing_code_hash;
  void broker_password_encrypted;
  return { ...safeAccount, account_number: maskAccountNumber(account.account_number) };
}
