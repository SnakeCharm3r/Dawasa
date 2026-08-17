import { ResourcePage } from "@/components/resource-page";
import { ReportCenter } from "@/components/report-center";

export default async function ModulePage({ params }: { params: Promise<{ module: string }> }) {
  const { module } = await params;
  if (module === "reports") return <ReportCenter />;
  return <ResourcePage moduleKey={module} />;
}
