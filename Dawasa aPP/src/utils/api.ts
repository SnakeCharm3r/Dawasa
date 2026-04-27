import { API_CONFIG } from "./config";
import { mockAuth, mockBills, mockPayments, mockComplaints } from "./mock-data";

// Conditional API exports - use mock in development, real API in production
export const auth = API_CONFIG.useMock ? mockAuth : {
  async login(ipNumber: string, password: string) {
    const response = await fetch(`${API_CONFIG.production.baseUrl}/auth/login`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${API_CONFIG.production.supabaseAnonKey}`,
      },
      body: JSON.stringify({ ipNumber, password }),
    });

    const result = await response.json();
    if (!response.ok) {
      throw new Error(result.error || "Login failed");
    }
    
    if (result.accessToken) {
      localStorage.setItem("accessToken", result.accessToken);
      localStorage.setItem("user", JSON.stringify(result.user));
    }
    
    return result;
  },

  async signup(data: {
    firstName: string;
    middleName?: string;
    lastName: string;
    location: string;
    ipNumber: string;
    password: string;
  }) {
    const response = await fetch(`${API_CONFIG.production.baseUrl}/auth/signup`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${API_CONFIG.production.supabaseAnonKey}`,
      },
      body: JSON.stringify(data),
    });

    const result = await response.json();
    if (!response.ok) {
      throw new Error(result.error || "Signup failed");
    }
    return result;
  },

  async getCurrentUser() {
    const token = localStorage.getItem("accessToken");
    if (!token) {
      throw new Error("Not authenticated");
    }

    const response = await fetch(`${API_CONFIG.production.baseUrl}/auth/me`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    const result = await response.json();
    if (!response.ok) {
      throw new Error(result.error || "Failed to get user");
    }
    return result.user;
  },

  logout() {
    localStorage.removeItem("accessToken");
    localStorage.removeItem("user");
    localStorage.removeItem("userIp");
  },

  getStoredUser() {
    const user = localStorage.getItem("user");
    return user ? JSON.parse(user) : null;
  },

  getToken() {
    return localStorage.getItem("accessToken");
  },
};

export const bills = API_CONFIG.useMock ? mockBills : {
  async getAll() {
    const token = auth.getToken();
    if (!token) throw new Error("Not authenticated");

    const response = await fetch(`${API_CONFIG.production.baseUrl}/bills`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    const result = await response.json();
    if (!response.ok) {
      throw new Error(result.error || "Failed to fetch bills");
    }
    return result.bills;
  },

  async create(billData: any) {
    const token = auth.getToken();
    if (!token) throw new Error("Not authenticated");

    const response = await fetch(`${API_CONFIG.production.baseUrl}/bills`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify(billData),
    });

    const result = await response.json();
    if (!response.ok) {
      throw new Error(result.error || "Failed to create bill");
    }
    return result.bill;
  },
};

export const payments = API_CONFIG.useMock ? mockPayments : {
  async generateControlNumber(data: {
    amount: number;
    billId?: string;
    paymentMethod?: string;
  }) {
    const token = auth.getToken();
    if (!token) throw new Error("Not authenticated");

    const response = await fetch(`${API_CONFIG.production.baseUrl}/payments/generate-control-number`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify(data),
    });

    const result = await response.json();
    if (!response.ok) {
      throw new Error(result.error || "Failed to generate control number");
    }
    return result;
  },

  async getHistory() {
    const token = auth.getToken();
    if (!token) throw new Error("Not authenticated");

    const response = await fetch(`${API_CONFIG.production.baseUrl}/payments`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    const result = await response.json();
    if (!response.ok) {
      throw new Error(result.error || "Failed to fetch payment history");
    }
    return result.payments;
  },
};

export const complaints = API_CONFIG.useMock ? mockComplaints : {
  async getAll() {
    const token = auth.getToken();
    if (!token) throw new Error("Not authenticated");

    const response = await fetch(`${API_CONFIG.production.baseUrl}/complaints`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    const result = await response.json();
    if (!response.ok) {
      throw new Error(result.error || "Failed to fetch complaints");
    }
    return result.complaints;
  },

  async submit(complaintData: {
    type: string;
    description: string;
    location?: string;
    contactMethod?: string;
    urgency?: string;
  }) {
    const token = auth.getToken();
    if (!token) throw new Error("Not authenticated");

    const response = await fetch(`${API_CONFIG.production.baseUrl}/complaints`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify(complaintData),
    });

    const result = await response.json();
    if (!response.ok) {
      throw new Error(result.error || "Failed to submit complaint");
    }
    return result.complaint;
  },
};
