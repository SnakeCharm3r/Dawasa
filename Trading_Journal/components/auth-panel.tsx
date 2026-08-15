'use client';

import { FormEvent, useState } from 'react';
import { useRouter } from 'next/navigation';
import { toast } from 'sonner';
import { KeyRound, LockKeyhole, Mail, UserRound } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type AuthMode = 'sign-in' | 'sign-up';

type Props = {
  signInWithPassword: (email: string, password: string) => Promise<void>;
  signUpWithPassword: (name: string, email: string, password: string) => Promise<{ requiresEmailConfirmation: boolean }>;
  signInWithGoogle: () => Promise<void>;
};

export function AuthPanel({
  signInWithPassword,
  signUpWithPassword,
  signInWithGoogle,
}: Props) {
  const [mode, setMode] = useState<AuthMode>('sign-in');
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [message, setMessage] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const router = useRouter();

  const run = async (action: () => Promise<void>) => {
    setLoading(true);
    setMessage(null);
    try {
      await action();
    } catch (error) {
      setMessage(error instanceof Error ? error.message : 'Authentication failed.');
    } finally {
      setLoading(false);
    }
  };

  const submitPassword = async (event: FormEvent) => {
    event.preventDefault();
    await run(async () => {
      if (mode === 'sign-in') {
        await signInWithPassword(email, password);
        return;
      }
      const result = await signUpWithPassword(name, email, password);
      if (result.requiresEmailConfirmation) {
        toast.success('Account created. Check your email to confirm it.');
        setMessage('Check your email to confirm your account, then sign in.');
        return;
      }

      toast.success('Account created successfully. Sign in to continue.');
      setMode('sign-in');
      setName('');
      setPassword('');
      router.replace('/login');
    });
  };

  return (
    <section className="w-full">
      <div className="mb-5 flex h-11 w-11 items-center justify-center rounded-full bg-primary/10 text-primary">
        <LockKeyhole className="h-5 w-5" />
      </div>
      <h1 className="text-xl font-semibold">{mode === 'sign-in' ? 'Sign in to your journal' : 'Create your journal account'}</h1>
      <p className="mt-1 text-sm text-muted-foreground">
        {mode === 'sign-in'
          ? 'Supabase Auth protects your journal and isolates your trading data.'
          : 'Add your name, email, and a secure password to get started.'}
      </p>

      <div className="mt-5 grid grid-cols-2 rounded-lg bg-muted/60 p-1 text-sm">
        <button
          type="button"
          className={mode === 'sign-in' ? 'rounded-md bg-background px-3 py-2 font-medium shadow-sm' : 'rounded-md px-3 py-2 text-muted-foreground'}
          onClick={() => { setMode('sign-in'); setMessage(null); }}
        >
          Sign in
        </button>
        <button
          type="button"
          className={mode === 'sign-up' ? 'rounded-md bg-background px-3 py-2 font-medium shadow-sm' : 'rounded-md px-3 py-2 text-muted-foreground'}
          onClick={() => { setMode('sign-up'); setMessage(null); }}
        >
          Create account
        </button>
      </div>

      <Button
        type="button"
        variant="outline"
        className="mt-4 w-full"
        disabled={loading}
        onClick={() => run(signInWithGoogle)}
      >
        <span className="mr-2 flex h-5 w-5 items-center justify-center rounded-full bg-foreground text-xs font-bold text-background">G</span>
        Continue with Google
      </Button>

      <div className="my-5 flex items-center gap-3 text-xs text-muted-foreground">
        <div className="h-px flex-1 bg-border" /> or use email <div className="h-px flex-1 bg-border" />
      </div>

      <form className="space-y-4" onSubmit={submitPassword}>
        {mode === 'sign-up' && (
          <div className="space-y-1.5">
            <Label htmlFor="auth-name">Name</Label>
            <div className="relative">
              <UserRound className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                id="auth-name"
                type="text"
                autoComplete="name"
                required
                minLength={2}
                maxLength={120}
                value={name}
                onChange={(event) => setName(event.target.value)}
                placeholder="Your full name"
                className="pl-9"
              />
            </div>
          </div>
        )}
        <div className="space-y-1.5">
          <Label htmlFor="auth-email">Email</Label>
          <div className="relative">
            <Mail className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              id="auth-email"
              type="email"
              autoComplete="email"
              required
              value={email}
              onChange={(event) => setEmail(event.target.value)}
              placeholder="you@example.com"
              className="pl-9"
            />
          </div>
        </div>
        <div className="space-y-1.5">
          <Label htmlFor="auth-password">Password</Label>
          <div className="relative">
            <KeyRound className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              id="auth-password"
              type="password"
              autoComplete={mode === 'sign-in' ? 'current-password' : 'new-password'}
              minLength={8}
              required
              value={password}
              onChange={(event) => setPassword(event.target.value)}
              className="pl-9"
            />
          </div>
        </div>
        <Button className="w-full" disabled={loading}>
          {loading ? 'Please wait…' : mode === 'sign-in' ? 'Sign in' : 'Create account'}
        </Button>
      </form>

      {message && <p className="mt-4 rounded-md bg-muted p-3 text-sm text-muted-foreground">{message}</p>}
    </section>
  );
}
