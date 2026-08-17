import { TenderResponseForm } from "@/components/portal/tender-response-form";
export default async function SupplierTenderPage({ params }: { params: Promise<{ tenderId: string }> }) { const { tenderId } = await params; return <TenderResponseForm tenderId={tenderId} />; }
