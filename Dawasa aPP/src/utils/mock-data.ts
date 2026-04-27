// Mock authentication for local development
export const mockAuth = {
  // Mock user database
  users: [
    {
      id: "1",
      firstName: "Test",
      lastName: "User",
      location: "Kinondoni, Dar es Salaam",
      ipNumber: "192.168.1.100",
      password: "test123",
      accessToken: "mock-token-123"
    },
    {
      id: "2", 
      firstName: "Demo",
      lastName: "Customer",
      location: "Ilala, Dar es Salaam",
      ipNumber: "192.168.1.101",
      password: "demo123",
      accessToken: "mock-token-456"
    }
  ],

  async login(ipNumber: string, password: string) {
    // Simulate network delay
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    const user = this.users.find(u => u.ipNumber === ipNumber && u.password === password);
    
    if (!user) {
      throw new Error("Invalid IP number or password");
    }
    
    // Store in localStorage
    localStorage.setItem("accessToken", user.accessToken);
    localStorage.setItem("user", JSON.stringify({
      id: user.id,
      firstName: user.firstName,
      lastName: user.lastName,
      location: user.location,
      ipNumber: user.ipNumber
    }));
    
    return {
      user: {
        id: user.id,
        firstName: user.firstName,
        lastName: user.lastName,
        location: user.location,
        ipNumber: user.ipNumber
      },
      accessToken: user.accessToken
    };
  },

  async signup(data: {
    firstName: string;
    middleName?: string;
    lastName: string;
    location: string;
    ipNumber: string;
    password: string;
  }) {
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    // Check if IP number already exists
    const existingUser = this.users.find(u => u.ipNumber === data.ipNumber);
    if (existingUser) {
      throw new Error("This IP number is already registered");
    }
    
    const newUser = {
      id: String(this.users.length + 1),
      ...data,
      accessToken: `mock-token-${this.users.length + 1}`
    };
    
    this.users.push(newUser);
    
    return {
      user: {
        id: newUser.id,
        firstName: newUser.firstName,
        lastName: newUser.lastName,
        location: newUser.location,
        ipNumber: newUser.ipNumber
      }
    };
  },

  async getCurrentUser() {
    const token = localStorage.getItem("accessToken");
    if (!token) {
      throw new Error("Not authenticated");
    }
    
    const user = this.users.find(u => u.accessToken === token);
    if (!user) {
      throw new Error("Invalid token");
    }
    
    return {
      id: user.id,
      firstName: user.firstName,
      lastName: user.lastName,
      location: user.location,
      ipNumber: user.ipNumber
    };
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
  }
};

// Mock bills data
export const mockBills = {
  async getAll() {
    await new Promise(resolve => setTimeout(resolve, 800));
    
    return [
      {
        id: "1",
        month: "January 2026",
        dueDate: "2026-01-31",
        amount: 45000,
        status: "Paid",
        paidDate: "2026-01-28",
        meterReading: "1234",
        units: 45
      },
      {
        id: "2", 
        month: "February 2026",
        dueDate: "2026-02-28",
        amount: 52000,
        status: "Pending",
        meterReading: "1279",
        units: 52
      },
      {
        id: "3",
        month: "March 2026", 
        dueDate: "2026-03-31",
        amount: 38000,
        status: "Pending",
        meterReading: "1317",
        units: 38
      }
    ];
  },

  async create(billData: any) {
    await new Promise(resolve => setTimeout(resolve, 800));
    return { id: "4", ...billData, status: "Pending" };
  }
};

// Mock payments data
export const mockPayments = {
  async generateControlNumber(data: { amount: number; billId?: string }) {
    await new Promise(resolve => setTimeout(resolve, 500));
    
    return {
      controlNumber: `CN${Date.now()}`,
      amount: data.amount,
      billId: data.billId,
      expiresAt: new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString()
    };
  },

  async getHistory() {
    await new Promise(resolve => setTimeout(resolve, 800));
    
    return [
      {
        id: "1",
        amount: 45000,
        controlNumber: "CN123456789",
        status: "Completed",
        date: "2026-01-28",
        method: "Mobile Money"
      }
    ];
  }
};

// Mock complaints data
export const mockComplaints = {
  async getAll() {
    await new Promise(resolve => setTimeout(resolve, 800));
    
    return [
      {
        id: "1",
        type: "Water Leakage",
        description: "Water leaking from main pipe",
        location: "Kinondoni, Street A",
        status: "In Progress",
        date: "2026-02-15",
        urgency: "High"
      }
    ];
  },

  async submit(complaintData: {
    type: string;
    description: string;
    location?: string;
    contactMethod?: string;
    urgency?: string;
  }) {
    await new Promise(resolve => setTimeout(resolve, 800));
    
    return {
      id: String(Date.now()),
      ...complaintData,
      status: "Submitted",
      date: new Date().toISOString().split('T')[0]
    };
  }
};
