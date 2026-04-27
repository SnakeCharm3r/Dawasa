import { createClient } from "@supabase/supabase-js";
import { projectId, publicAnonKey } from "/utils/supabase/info";

export const supabase = createClient(
  `https://${projectId}.supabase.co`,
  publicAnonKey
);

const API_URL = `https://${projectId}.supabase.co/functions/v1/make-server-2abd97f5`;

// ─── Demo / Development Mode ──────────────────────────────────────────────────
// Set to true to use local fixture data instead of the live backend.
// Any meter number will work in demo mode.
const DEMO_MODE = true;

// ─── Types ────────────────────────────────────────────────────────────────────

export interface CustomerInfo {
  customerName: string;
  accountNumber: string;
  meterNumber: string;
  controlNumber: string;
  category: string;
  zone: string;
  route: string;
}

export interface BillStatement {
  id: string;
  date: string;             // "16-02-2026"
  openingBalance: number;
  credit: number;
  debit: number;
  closingBalance: number;
  reference: string;        // "BILL-220044644"
  description: string;      // "Bill of 2026-2 : Readings 493.0 to 518.0"
  year: number;
  month: number;
  openingReading?: number;
  closingReading?: number;
  consumption?: number;     // m³
  status: "Paid" | "Pending" | "Partial";
  type: "bill" | "payment";
}

export interface PaymentControlNumber {
  controlNumber: string;
  amount: number;
  billReference: string;
  generatedAt: string;
  expiresAt: string;
  status: "active" | "used" | "expired";
}

export interface Receipt {
  id: string;
  controlNumber: string;
  amount: number;
  paidAt: string;
  method: string;
  reference: string;
  meterNumber: string;
}

export interface Complaint {
  id: string;
  meterNumber: string;
  type: ComplaintType;
  description: string;
  location?: string;
  urgency: "low" | "medium" | "high" | "critical";
  status: "Pending" | "In Progress" | "Resolved";
  createdAt: string;
  response?: string;
}

export type ComplaintType =
  | "Low Water Pressure / Low Flow"
  | "Water Leakage"
  | "Pipe / Equipment Damage"
  | "Wrong Bill / Billing Error"
  | "Water Quality Issue"
  | "No Water Supply"
  | "Meter Reading Dispute"
  | "Other";

// ─── Demo fixture data ────────────────────────────────────────────────────────

const DEMO_CUSTOMER: CustomerInfo = {
  customerName: "JOHN MWANGI KAMAU",
  accountNumber: "ACC-220044644",
  meterNumber: "12345678",
  controlNumber: "990012345678",
  category: "Domestic",
  zone: "Kinondoni",
  route: "Route 04 – Mbezi Beach",
};

const DEMO_STATEMENTS: BillStatement[] = [
  { id:"s1",  date:"16-02-2026", openingBalance:45000,  credit:0,     debit:57200,  closingBalance:102200, reference:"BILL-220044644-0226", description:"Bill of 2026-2 : Readings 493.0 to 518.0", year:2026, month:2,  openingReading:493, closingReading:518, consumption:25, status:"Pending", type:"bill"    },
  { id:"s2",  date:"05-02-2026", openingBalance:91500,  credit:91500, debit:0,      closingBalance:0,      reference:"PAY-20260205-0091",   description:"Payment received – M-Pesa Ref TXN9983211",    year:2026, month:2,  openingReading:0,   closingReading:0,   consumption:0,  status:"Paid",    type:"payment" },
  { id:"s3",  date:"16-01-2026", openingBalance:0,      credit:0,     debit:91500,  closingBalance:91500,  reference:"BILL-220044644-0126", description:"Bill of 2026-1 : Readings 466.0 to 493.0", year:2026, month:1,  openingReading:466, closingReading:493, consumption:27, status:"Paid",    type:"bill"    },
  { id:"s4",  date:"03-01-2026", openingBalance:83200,  credit:83200, debit:0,      closingBalance:0,      reference:"PAY-20260103-0083",   description:"Payment received – Tigo Pesa Ref TXN7712009", year:2026, month:1,  openingReading:0,   closingReading:0,   consumption:0,  status:"Paid",    type:"payment" },
  { id:"s5",  date:"16-12-2025", openingBalance:0,      credit:0,     debit:83200,  closingBalance:83200,  reference:"BILL-220044644-1225", description:"Bill of 2025-12 : Readings 440.0 to 466.0", year:2025, month:12, openingReading:440, closingReading:466, consumption:26, status:"Paid",    type:"bill"    },
  { id:"s6",  date:"10-12-2025", openingBalance:76500,  credit:76500, debit:0,      closingBalance:0,      reference:"PAY-20251210-0076",   description:"Payment received – Airtel Money Ref TXN5541002", year:2025, month:12, openingReading:0,  closingReading:0,   consumption:0,  status:"Paid",    type:"payment" },
  { id:"s7",  date:"16-11-2025", openingBalance:0,      credit:0,     debit:76500,  closingBalance:76500,  reference:"BILL-220044644-1125", description:"Bill of 2025-11 : Readings 416.0 to 440.0", year:2025, month:11, openingReading:416, closingReading:440, consumption:24, status:"Paid",    type:"bill"    },
  { id:"s8",  date:"14-11-2025", openingBalance:68000,  credit:68000, debit:0,      closingBalance:0,      reference:"PAY-20251114-0068",   description:"Payment received – M-Pesa Ref TXN3301887",    year:2025, month:11, openingReading:0,   closingReading:0,   consumption:0,  status:"Paid",    type:"payment" },
  { id:"s9",  date:"16-10-2025", openingBalance:0,      credit:0,     debit:68000,  closingBalance:68000,  reference:"BILL-220044644-1025", description:"Bill of 2025-10 : Readings 393.0 to 416.0", year:2025, month:10, openingReading:393, closingReading:416, consumption:23, status:"Paid",    type:"bill"    },
  { id:"s10", date:"16-09-2025", openingBalance:0,      credit:0,     debit:64800,  closingBalance:64800,  reference:"BILL-220044644-0925", description:"Bill of 2025-9 : Readings 371.0 to 393.0",  year:2025, month:9,  openingReading:371, closingReading:393, consumption:22, status:"Paid",    type:"bill"    },
];

const DEMO_CONTROL_NUMBER: PaymentControlNumber = {
  controlNumber: "990012345678",
  amount: 102200,
  billReference: "BILL-220044644-0226",
  generatedAt: new Date().toISOString(),
  expiresAt: new Date(Date.now() + 48 * 60 * 60 * 1000).toISOString(),
  status: "active",
};

const DEMO_COMPLAINTS: Complaint[] = [
  {
    id: "CMP-001",
    meterNumber: "12345678",
    type: "Low Water Pressure / Low Flow",
    description: "Water pressure has been very low since Monday morning. Barely enough to fill a bucket.",
    location: "House No. 14, Mbezi Beach",
    urgency: "medium",
    status: "In Progress",
    createdAt: "2026-02-10T08:30:00Z",
    response: "A technician has been dispatched and will visit between 2–5 PM on 12 Feb 2026.",
  },
  {
    id: "CMP-002",
    meterNumber: "12345678",
    type: "Wrong Bill / Billing Error",
    description: "My bill for January shows 50 m³ consumption but my meter reading was only 27 m³.",
    location: "",
    urgency: "high",
    status: "Resolved",
    createdAt: "2026-01-20T10:00:00Z",
    response: "Bill has been reviewed and corrected. A credit of TZS 18,400 has been applied to your account.",
  },
];

// ─── Session helpers (meter-based, no user account needed) ────────────────────

export const session = {
  saveMeter(meterNumber: string, customerInfo: CustomerInfo) {
    sessionStorage.setItem("meterNumber", meterNumber);
    sessionStorage.setItem("customerInfo", JSON.stringify(customerInfo));
  },
  getMeter(): string | null {
    return sessionStorage.getItem("meterNumber");
  },
  getCustomer(): CustomerInfo | null {
    const raw = sessionStorage.getItem("customerInfo");
    return raw ? JSON.parse(raw) : null;
  },
  clear() {
    sessionStorage.removeItem("meterNumber");
    sessionStorage.removeItem("customerInfo");
  },

  saveReceipt(receipt: Receipt) {
    const existing = this.getReceipts();
    existing.unshift(receipt);
    localStorage.setItem("dawasa_receipts", JSON.stringify(existing.slice(0, 50)));
  },
  getReceipts(): Receipt[] {
    const raw = localStorage.getItem("dawasa_receipts");
    return raw ? JSON.parse(raw) : [];
  },
};

// ─── Meter & Customer Lookup (no login) ───────────────────────────────────────

export const meter = {
  async lookup(meterNumber: string): Promise<CustomerInfo> {
    if (DEMO_MODE) {
      await new Promise(r => setTimeout(r, 800));
      return { ...DEMO_CUSTOMER, meterNumber };
    }
    const response = await fetch(`${API_URL}/meter/lookup`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${publicAnonKey}`,
      },
      body: JSON.stringify({ meterNumber }),
    });
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || "Meter number not found. Please check and try again.");
    return result.customer as CustomerInfo;
  },
};

// ─── Bills / Statement ────────────────────────────────────────────────────────

export const bills = {
  async getStatement(meterNumber: string, filters?: {
    year?: number;
    month?: number;
    fromDate?: string;
    toDate?: string;
  }): Promise<BillStatement[]> {
    if (DEMO_MODE) {
      await new Promise(r => setTimeout(r, 600));
      let data = DEMO_STATEMENTS;
      if (filters?.year)  data = data.filter(s => s.year  === filters.year);
      if (filters?.month) data = data.filter(s => s.month === filters.month);
      return data;
    }
    const params = new URLSearchParams({ meterNumber });
    if (filters?.year) params.append("year", String(filters.year));
    if (filters?.month) params.append("month", String(filters.month));
    if (filters?.fromDate) params.append("fromDate", filters.fromDate);
    if (filters?.toDate) params.append("toDate", filters.toDate);
    const response = await fetch(`${API_URL}/bills/statement?${params}`, {
      headers: { Authorization: `Bearer ${publicAnonKey}` },
    });
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || "Failed to fetch bill statement");
    return result.statements as BillStatement[];
  },

  async exportEmail(meterNumber: string, email: string, filters?: {
    year?: number;
    month?: number;
  }): Promise<{ message: string }> {
    if (DEMO_MODE) {
      await new Promise(r => setTimeout(r, 1000));
      return { message: `Demo: Statement would be sent to ${email}` };
    }
    const response = await fetch(`${API_URL}/bills/export-email`, {
      method: "POST",
      headers: { "Content-Type": "application/json", Authorization: `Bearer ${publicAnonKey}` },
      body: JSON.stringify({ meterNumber, email, ...filters }),
    });
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || "Failed to send email report");
    return result;
  },

  generateCSV(statements: BillStatement[], customer: CustomerInfo): string {
    const header = [
      "Date", "Opening Balance (TZS)", "Credit (TZS)", "Debit (TZS)",
      "Closing Balance (TZS)", "Reference", "Consumption (m³)", "Description", "Status"
    ].join(",");

    const rows = statements.map(s => [
      s.date,
      s.openingBalance.toFixed(2),
      s.credit.toFixed(2),
      s.debit.toFixed(2),
      s.closingBalance.toFixed(2),
      s.reference,
      s.consumption ?? "",
      `"${s.description}"`,
      s.status,
    ].join(","));

    const meta = [
      `"DAWASA - Dar-es-Salaam Water Supply and Sanitation Authority"`,
      `"Customer Statement"`,
      `"Customer Name:","${customer.customerName}"`,
      `"Account Number:","${customer.accountNumber}"`,
      `"Meter Number:","${customer.meterNumber}"`,
      `"Zone:","${customer.zone}"`,
      `"Category:","${customer.category}"`,
      `""`,
    ];

    return [...meta, header, ...rows].join("\n");
  },
};

// ─── Payments ─────────────────────────────────────────────────────────────────

export const payments = {
  async getControlNumber(meterNumber: string): Promise<PaymentControlNumber | null> {
    if (DEMO_MODE) {
      await new Promise(r => setTimeout(r, 500));
      return { ...DEMO_CONTROL_NUMBER, generatedAt: new Date().toISOString() };
    }
    const response = await fetch(`${API_URL}/payments/control-number?meterNumber=${meterNumber}`, {
      headers: { Authorization: `Bearer ${publicAnonKey}` },
    });
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || "Failed to fetch control number");
    return result.controlNumber ?? null;
  },

  async generateControlNumber(meterNumber: string, amount: number, billReference?: string): Promise<PaymentControlNumber> {
    if (DEMO_MODE) {
      await new Promise(r => setTimeout(r, 900));
      const cn = `99${Date.now().toString().slice(-10)}`;
      return {
        controlNumber: cn,
        amount,
        billReference: billReference ?? "BILL-220044644-0226",
        generatedAt: new Date().toISOString(),
        expiresAt: new Date(Date.now() + 48 * 60 * 60 * 1000).toISOString(),
        status: "active",
      };
    }
    const response = await fetch(`${API_URL}/payments/generate-control-number`, {
      method: "POST",
      headers: { "Content-Type": "application/json", Authorization: `Bearer ${publicAnonKey}` },
      body: JSON.stringify({ meterNumber, amount, billReference }),
    });
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || "Failed to generate control number");
    return result.controlNumber as PaymentControlNumber;
  },

  async confirmPayment(controlNumber: string, meterNumber: string): Promise<Receipt> {
    if (DEMO_MODE) {
      await new Promise(r => setTimeout(r, 700));
      return {
        id: `RCP-${Date.now()}`,
        controlNumber,
        amount: DEMO_CONTROL_NUMBER.amount,
        paidAt: new Date().toISOString(),
        method: "Demo – M-Pesa",
        reference: `TXN${Math.floor(Math.random() * 9000000 + 1000000)}`,
        meterNumber,
      };
    }
    const response = await fetch(`${API_URL}/payments/confirm`, {
      method: "POST",
      headers: { "Content-Type": "application/json", Authorization: `Bearer ${publicAnonKey}` },
      body: JSON.stringify({ controlNumber, meterNumber }),
    });
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || "Failed to confirm payment");
    return result.receipt as Receipt;
  },
};

// ─── Complaints ───────────────────────────────────────────────────────────────

export const complaints = {
  async getByMeter(meterNumber: string): Promise<Complaint[]> {
    if (DEMO_MODE) {
      await new Promise(r => setTimeout(r, 500));
      return DEMO_COMPLAINTS.map(c => ({ ...c, meterNumber }));
    }
    const response = await fetch(`${API_URL}/complaints?meterNumber=${meterNumber}`, {
      headers: { Authorization: `Bearer ${publicAnonKey}` },
    });
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || "Failed to fetch complaints");
    return result.complaints as Complaint[];
  },

  async submit(data: {
    meterNumber: string;
    type: ComplaintType;
    description: string;
    location?: string;
    urgency: "low" | "medium" | "high" | "critical";
    contactPhone?: string;
  }): Promise<Complaint> {
    if (DEMO_MODE) {
      await new Promise(r => setTimeout(r, 800));
      return {
        id: `CMP-${Date.now()}`,
        meterNumber: data.meterNumber,
        type: data.type,
        description: data.description,
        location: data.location,
        urgency: data.urgency,
        status: "Pending",
        createdAt: new Date().toISOString(),
      };
    }
    const response = await fetch(`${API_URL}/complaints`, {
      method: "POST",
      headers: { "Content-Type": "application/json", Authorization: `Bearer ${publicAnonKey}` },
      body: JSON.stringify(data),
    });
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || "Failed to submit complaint");
    return result.complaint as Complaint;
  },
};
