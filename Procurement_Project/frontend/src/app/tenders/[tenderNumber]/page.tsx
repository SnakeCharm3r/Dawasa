import { PublicFooter, PublicHeader } from "@/components/portal/public-shell";
import { TenderDetail } from "@/components/portal/tender-detail";

export default async function TenderPage({ params }: { params: Promise<{ tenderNumber: string }> }) { const { tenderNumber } = await params; return <div className="portal-site"><PublicHeader /><main className="portal-section portal-container"><TenderDetail tenderNumber={tenderNumber} /></main><PublicFooter /></div>; }
