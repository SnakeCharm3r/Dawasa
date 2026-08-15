import { getSupabaseClient } from '@/lib/supabase';

type EdgeError = Error & { context?: Response };

export async function invokeJournalApi<T>(body: Record<string, unknown>): Promise<T> {
  const { data, error } = await getSupabaseClient().functions.invoke<T>('journal-api', { body });
  if (!error) return data as T;

  let message = error.message || 'The server could not complete this request.';
  const response = (error as EdgeError).context;
  if (response instanceof Response) {
    try {
      const details = await response.clone().json() as { error?: string };
      if (details.error) message = details.error;
    } catch {
      // Keep the SDK error when a response has no JSON body.
    }
  }
  throw new Error(message);
}
