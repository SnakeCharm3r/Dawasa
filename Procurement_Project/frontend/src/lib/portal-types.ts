export type Category = { id: number; name: string; code: string };
export type TenderItem = { id: number; item_name: string; specification: string | null; quantity: string; unit: string };
export type Tender = {
  id: number; tender_number: string; title: string; public_summary: string; tender_type: string;
  status: string; publication_at: string; submission_deadline: string; expected_delivery_date?: string | null;
  delivery_location?: string | null; contact_email?: string; contact_phone?: string | null;
  eligibility_requirements?: string | null; submission_instructions?: string | null; terms_and_conditions?: string | null;
  category: Category | null; items?: TenderItem[];
};
export type Paginated<T> = { data: T[]; current_page: number; last_page: number; total: number };
