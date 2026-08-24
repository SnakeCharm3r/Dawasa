'use client';

import { LayoutDashboard, BookOpen, CalendarRange, History, Moon, Sun, LineChart, Settings, ShieldCheck, UserCog } from 'lucide-react';
import { useTheme } from '@/components/theme-provider';
import { cn } from '@/lib/utils';

export type PageView = 'dashboard' | 'trades' | 'calendar' | 'accounts' | 'settings' | 'admin' | 'activity';

const NAV_ITEMS: { id: PageView; label: string; icon: typeof LayoutDashboard }[] = [
  { id: 'dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { id: 'trades', label: 'Trade Log', icon: BookOpen },
  { id: 'calendar', label: 'Calendar', icon: CalendarRange },
  { id: 'accounts', label: 'Accounts', icon: Settings },
  { id: 'settings', label: 'Settings', icon: UserCog },
];

export function NavBar({ view, onChange, onSignOut, userLabel, isAdmin = false }: { view: PageView; onChange: (v: PageView) => void; onSignOut?: () => void; userLabel?: string | null; isAdmin?: boolean }) {
  const { theme, toggle } = useTheme();

  return (
    <header className="sticky top-0 z-40 border-b border-border/60 bg-background/80 backdrop-blur-md">
      <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <div className="flex items-center gap-2.5">
          <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-700 text-white shadow-sm">
            <LineChart className="h-5 w-5" />
          </div>
          <div className="hidden sm:block">
            <div className="text-sm font-semibold leading-tight">Trade Journal</div>
            <div className="text-[11px] leading-tight text-muted-foreground">Forex · Gold · Stocks · Crypto</div>
          </div>
        </div>

        <nav className="flex items-center gap-1 rounded-lg bg-muted/60 p-1">
          {[...NAV_ITEMS, ...(isAdmin ? [
            { id: 'admin' as const, label: 'Admin', icon: ShieldCheck },
            { id: 'activity' as const, label: 'Login Activity', icon: History },
          ] : [])].map((item) => {
            const Icon = item.icon;
            const active = view === item.id;
            return (
              <button
                key={item.id}
                onClick={() => onChange(item.id)}
                className={cn(
                  'flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition-all',
                  active
                    ? 'bg-background text-foreground shadow-sm'
                    : 'text-muted-foreground hover:text-foreground'
                )}
              >
                <Icon className="h-4 w-4" />
                <span className="hidden sm:inline">{item.label}</span>
              </button>
            );
          })}
        </nav>

        <div className="flex items-center gap-1">
          {userLabel && <span className="hidden max-w-40 truncate px-2 text-xs text-muted-foreground lg:block">{userLabel}</span>}
          {onSignOut && <button onClick={onSignOut} className="hidden rounded-md px-2 py-1 text-xs text-muted-foreground hover:bg-accent sm:block">Sign out</button>}
          <button
            onClick={toggle}
            aria-label="Toggle theme"
            className="flex h-9 w-9 items-center justify-center rounded-md border border-input bg-background text-foreground transition-colors hover:bg-accent"
          >
            {theme === 'dark' ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
          </button>
        </div>
      </div>
    </header>
  );
}
