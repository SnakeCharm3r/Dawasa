"use client";

import { useAuth } from "@/components/auth-provider";
import { ApiError } from "@/lib/api";
import { Eye, EyeOff, LoaderCircle, LockKeyhole, Mail, ShieldCheck } from "lucide-react";
import { FormEvent, useState } from "react";

export function LoginForm() {
  const { login, loading, user } = useAuth();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [remember, setRemember] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState("");

  async function submit(event: FormEvent) {
    event.preventDefault();
    setError("");
    setSubmitting(true);
    try {
      await login(email, password, remember);
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : "Unable to sign in.");
    } finally {
      setSubmitting(false);
    }
  }

  if (loading || user) return <div className="login-loading"><LoaderCircle className="spin" size={24} />Checking session</div>;

  return (
    <div className="login-page">
      <section className="login-identity">
        <div className="login-brand"><span className="brand-mark large"><ShieldCheck size={27} /></span><span><strong>Procure</strong><small>Control office</small></span></div>
        <div className="identity-copy">
          <p className="eyebrow">Procurement operations</p>
          <h1>One accountable path from request to payment.</h1>
          <p>Review work queues, protect approved budgets, and keep every purchasing decision traceable.</p>
        </div>
        <div className="identity-foot"><LockKeyhole size={17} /><span>Role-controlled access and immutable activity history</span></div>
      </section>
      <main className="login-panel">
        <form className="login-form" onSubmit={submit}>
          <div className="form-heading"><p className="eyebrow">Secure workspace</p><h2>Sign in</h2><p>Use your organisation account to continue.</p></div>
          {error && <div className="form-alert" role="alert">{error}</div>}
          <label className="field"><span>Email address</span><div className="input-with-icon"><Mail size={18} /><input type="email" value={email} onChange={(event) => setEmail(event.target.value)} autoComplete="email" required placeholder="name@organisation.com" /></div></label>
          <label className="field"><span>Password</span><div className="input-with-icon"><LockKeyhole size={18} /><input type={showPassword ? "text" : "password"} value={password} onChange={(event) => setPassword(event.target.value)} autoComplete="current-password" required placeholder="Enter your password" /><button type="button" className="input-action" onClick={() => setShowPassword((value) => !value)} title={showPassword ? "Hide password" : "Show password"}>{showPassword ? <EyeOff size={18} /> : <Eye size={18} />}</button></div></label>
          <label className="checkbox-field"><input type="checkbox" checked={remember} onChange={(event) => setRemember(event.target.checked)} /><span>Keep me signed in on this device</span></label>
          <button className="primary-button wide" disabled={submitting}>{submitting && <LoaderCircle className="spin" size={17} />}{submitting ? "Signing in" : "Sign in"}</button>
        </form>
      </main>
    </div>
  );
}
