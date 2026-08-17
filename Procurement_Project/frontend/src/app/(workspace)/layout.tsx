import { AppShell } from "@/components/app-shell";
import { EntityScopeProvider } from "@/components/entity-scope-provider";

export default function WorkspaceLayout({ children }: { children: React.ReactNode }) {
  return <EntityScopeProvider><AppShell>{children}</AppShell></EntityScopeProvider>;
}
