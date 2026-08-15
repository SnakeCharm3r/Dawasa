import { ResourcePage } from "@/components/resource-page";

export default async function ModulePage({ params }: { params: Promise<{ module: string }> }) {
  const { module } = await params;
  return <ResourcePage moduleKey={module} />;
}
