export type Role =
  | "super_admin"
  | "gm"
  | "ceo"
  | "accountant"
  | "procurement_officer"
  | "department_head"
  | "requester"
  | "auditor"
  | "line_manager"
  | "receiving_officer"
  | "storekeeper"
  | "supplier";

export type AuthUser = {
  id: number;
  name: string;
  first_name: string | null;
  last_name: string | null;
  email: string;
  email_verified_at: string | null;
  job_title: string | null;
  is_line_manager: boolean;
  roles: Role[];
  department: {
    id: number;
    name: string;
    business_entity: { id: number; name: string } | null;
  } | null;
  supplier: {
    id: number;
    name: string;
    application_reference: string;
    status: string;
  } | null;
};

export type JsonRecord = Record<string, unknown>;

export type Pagination = {
  current_page?: number;
  last_page?: number;
  per_page?: number;
  total?: number;
};
