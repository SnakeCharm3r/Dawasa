import { createClient } from "@supabase/supabase-js";
import { projectId, publicAnonKey } from "/utils/supabase/info";

// Create Supabase client
export const supabase = createClient(
  `https://${projectId}.supabase.co`,
  publicAnonKey
);

const API_URL = `https://${projectId}.supabase.co/functions/v1/make-server-2abd97f5`;

// Auth helpers
export const auth = {
  async signup(data: {
    firstName: string;
    middleName?: string;
    lastName: string;
    location: string;
    ipNumber: string;
    password: string;
  }) {
    const response = await fetch(`${API_URL}/auth/signup`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${publicAnonKey}`,
      },
      body: JSON.stringify(data),
    });

    const result = await response.json();
    if (!response.ok) {
      throw new Error(result.error || "Signup failed");
    }
    return result;
  },

  async login(ipNumber: string, password: string) {
    const response = await fetch(`${API_URL}/auth/login`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${publicAnonKey}`,
      },
      body: JSON.stringify({ ipNumber, password }),
    });

    const result = await response.json();
    if (!response.ok) {
      throw new Error(result.error || "Login failed");
    }
    
    // Store access token and user data
    if (result.accessToken) {
      localStorage.setItem("accessToken", result.accessToken);
      localStorage.setItem("user", JSON.stringify(result.user));
    }
    
    return result;
  },

  async getCurrentUser() {
    const token = localStorage.getItem("accessToken");
    if (!token) {
      throw new Error("Not authenticated");
    }

    const response = await fetch(`${API_URL}/auth/me`, {
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

// Bills API
export const bills = {
  async getAll() {
    const token = auth.getToken();
    if (!token) throw new Error("Not authenticated");

    const response = await fetch(`${API_URL}/bills`, {
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

    const response = await fetch(`${API_URL}/bills`, {
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

// Payments API
export const payments = {
  async generateControlNumber(data: {
    amount: number;
    billId?: string;
    paymentMethod?: string;
  }) {
    const token = auth.getToken();
    if (!token) throw new Error("Not authenticated");

    const response = await fetch(`${API_URL}/payments/generate-control-number`, {
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

    const response = await fetch(`${API_URL}/payments`, {
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

// Complaints API
export const complaints = {
  async getAll() {
    const token = auth.getToken();
    if (!token) throw new Error("Not authenticated");

    const response = await fetch(`${API_URL}/complaints`, {
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

    const response = await fetch(`${API_URL}/complaints`, {
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
