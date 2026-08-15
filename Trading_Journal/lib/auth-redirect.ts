export function getAuthRedirectUrl() {
  const configured = process.env.NEXT_PUBLIC_SITE_URL?.trim().replace(/\/$/, '');
  if (configured) return `${configured}/login`;
  if (typeof window !== 'undefined') return `${window.location.origin}/login`;
  return '';
}
