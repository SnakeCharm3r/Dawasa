import { Inbox, LoaderCircle } from "lucide-react";

export function StatusBadge({ status }: { status: string }) { return <span className={`portal-status portal-status-${status.replaceAll("_", "-")}`}>{status.replaceAll("_", " ")}</span>; }
export function LoadingState({ label = "Loading" }: { label?: string }) { return <div className="portal-state"><LoaderCircle className="spin" size={24} /><p>{label}…</p></div>; }
export function EmptyState({ title, copy }: { title: string; copy: string }) { return <div className="portal-state"><Inbox size={28} /><h3>{title}</h3><p>{copy}</p></div>; }
export function FieldError({ message }: { message?: string }) { return message ? <small className="field-error">{message}</small> : null; }
