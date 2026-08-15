export type Role =
  | "super_admin"
  | "gm"
  | "accountant"
  | "procurement_officer"
  | "department_head"
  | "requester"
  | "auditor"
  | "line_manager"
  | "receiving_officer"
  | "storekeeper";

export type AuthUser = {
  id: number;
  name: string;
  first_name: string | null;
  last_name: string | null;
  email: string;
  job_title: string | null;
  is_line_manager: boolean;
  roles: Role[];
  department: {
    id: number;
    name: string;
    business_entity: { id: number; name: string } | null;
  } | null;
};

export type JsonRecord = Record<string, unknown>;

export type Pagination = {
  current_page?: number;
  last_page?: number;
  per_page?: number;
  total?: number;
};
