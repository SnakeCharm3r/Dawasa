import { TenderBidManager } from "@/components/admin/tender-bid-manager";

export default async function TenderBidPage({ params }: PageProps<"/admin-tenders/[tenderId]">) {
  const { tenderId } = await params;
  return <TenderBidManager tenderId={tenderId} />;
}
