import { Hono } from "npm:hono";
import { cors } from "npm:hono/cors";
import { logger } from "npm:hono/logger";
import { createClient } from "npm:@supabase/supabase-js@2";
import * as kv from "./kv_store.tsx";

const app = new Hono();

// Initialize Supabase client
const supabase = createClient(
  Deno.env.get("SUPABASE_URL") ?? "",
  Deno.env.get("SUPABASE_SERVICE_ROLE_KEY") ?? ""
);

// Enable logger
app.use("*", logger(console.log));

// Enable CORS for all routes and methods
app.use(
  "/*",
  cors({
    origin: "*",
    allowHeaders: ["Content-Type", "Authorization"],
    allowMethods: ["GET", "POST", "PUT", "DELETE", "OPTIONS"],
    exposeHeaders: ["Content-Length"],
    maxAge: 600,
  })
);

// Middleware to verify user authentication
async function verifyAuth(authHeader: string | undefined) {
  if (!authHeader) {
    return null;
  }
  
  const token = authHeader.split(" ")[1];
  if (!token) {
    return null;
  }

  const { data: { user }, error } = await supabase.auth.getUser(token);
  if (error || !user) {
    return null;
  }

  return user;
}

// Health check endpoint
app.get("/make-server-2abd97f5/health", (c) => {
  return c.json({ status: "ok" });
});

// ============= AUTH ENDPOINTS =============

// User signup
app.post("/make-server-2abd97f5/auth/signup", async (c) => {
  try {
    const body = await c.req.json();
    const { firstName, middleName, lastName, location, ipNumber, password } = body;

    if (!firstName || !lastName || !location || !ipNumber || !password) {
      return c.json({ error: "Missing required fields" }, 400);
    }

    // Create auth user with IP number as email (unique identifier)
    const email = `${ipNumber.replace(/\./g, "-")}@dawasa.local`;
    
    const { data: authData, error: authError } = await supabase.auth.admin.createUser({
      email,
      password,
      email_confirm: true, // Auto-confirm since no email server configured
      user_metadata: {
        firstName,
        middleName: middleName || "",
        lastName,
        location,
        ipNumber,
        createdAt: new Date().toISOString(),
      },
    });

    if (authError) {
      console.log("Auth error during signup:", authError);
      return c.json({ error: authError.message }, 400);
    }

    // Store user data in KV store
    const userId = authData.user.id;
    const userData = {
      id: userId,
      firstName,
      middleName: middleName || "",
      lastName,
      location,
      ipNumber,
      email,
      createdAt: new Date().toISOString(),
    };

    await kv.set(`user:${ipNumber}`, userData);
    await kv.set(`user:id:${userId}`, userData);

    // Create initial empty bills array
    await kv.set(`bills:${userId}`, []);

    return c.json({ 
      success: true, 
      message: "User created successfully",
      user: userData 
    });
  } catch (error) {
    console.log("Error during signup:", error);
    return c.json({ error: "Failed to create user" }, 500);
  }
});

// User login
app.post("/make-server-2abd97f5/auth/login", async (c) => {
  try {
    const body = await c.req.json();
    const { ipNumber, password } = body;

    if (!ipNumber || !password) {
      return c.json({ error: "IP number and password required" }, 400);
    }

    // Convert IP number to email format
    const email = `${ipNumber.replace(/\./g, "-")}@dawasa.local`;

    const { data, error } = await supabase.auth.signInWithPassword({
      email,
      password,
    });

    if (error) {
      console.log("Login error:", error);
      return c.json({ error: "Invalid credentials" }, 401);
    }

    // Get user data from KV store
    const userData = await kv.get(`user:${ipNumber}`);

    return c.json({
      success: true,
      accessToken: data.session.access_token,
      user: userData,
    });
  } catch (error) {
    console.log("Error during login:", error);
    return c.json({ error: "Login failed" }, 500);
  }
});

// Get current user
app.get("/make-server-2abd97f5/auth/me", async (c) => {
  const user = await verifyAuth(c.req.header("Authorization"));
  
  if (!user) {
    return c.json({ error: "Unauthorized" }, 401);
  }

  const userData = await kv.get(`user:id:${user.id}`);
  
  if (!userData) {
    return c.json({ error: "User not found" }, 404);
  }

  return c.json({ user: userData });
});

// ============= USER ENDPOINTS =============

// Get user by IP number
app.get("/make-server-2abd97f5/users/:ipNumber", async (c) => {
  const user = await verifyAuth(c.req.header("Authorization"));
  
  if (!user) {
    return c.json({ error: "Unauthorized" }, 401);
  }

  const ipNumber = c.req.param("ipNumber");
  const userData = await kv.get(`user:${ipNumber}`);

  if (!userData) {
    return c.json({ error: "User not found" }, 404);
  }

  return c.json({ user: userData });
});

// Update user
app.put("/make-server-2abd97f5/users/:ipNumber", async (c) => {
  const user = await verifyAuth(c.req.header("Authorization"));
  
  if (!user) {
    return c.json({ error: "Unauthorized" }, 401);
  }

  const ipNumber = c.req.param("ipNumber");
  const body = await c.req.json();

  const existingUser = await kv.get(`user:${ipNumber}`);
  if (!existingUser) {
    return c.json({ error: "User not found" }, 404);
  }

  const updatedUser = { ...existingUser, ...body, updatedAt: new Date().toISOString() };
  
  await kv.set(`user:${ipNumber}`, updatedUser);
  await kv.set(`user:id:${user.id}`, updatedUser);

  return c.json({ user: updatedUser });
});

// ============= BILLS ENDPOINTS =============

// Get all bills for a user
app.get("/make-server-2abd97f5/bills", async (c) => {
  const user = await verifyAuth(c.req.header("Authorization"));
  
  if (!user) {
    return c.json({ error: "Unauthorized" }, 401);
  }

  const bills = await kv.get(`bills:${user.id}`) || [];
  
  return c.json({ bills });
});

// Create a new bill
app.post("/make-server-2abd97f5/bills", async (c) => {
  const user = await verifyAuth(c.req.header("Authorization"));
  
  if (!user) {
    return c.json({ error: "Unauthorized" }, 401);
  }

  const body = await c.req.json();
  const bills = await kv.get(`bills:${user.id}`) || [];
  
  const newBill = {
    id: `BILL-${Date.now()}`,
    userId: user.id,
    ...body,
    createdAt: new Date().toISOString(),
  };

  bills.push(newBill);
  await kv.set(`bills:${user.id}`, bills);

  return c.json({ bill: newBill });
});

// Get a specific bill
app.get("/make-server-2abd97f5/bills/:billId", async (c) => {
  const user = await verifyAuth(c.req.header("Authorization"));
  
  if (!user) {
    return c.json({ error: "Unauthorized" }, 401);
  }

  const billId = c.req.param("billId");
  const bills = await kv.get(`bills:${user.id}`) || [];
  
  const bill = bills.find((b: any) => b.id === billId);
  
  if (!bill) {
    return c.json({ error: "Bill not found" }, 404);
  }

  return c.json({ bill });
});

// Update a bill
app.put("/make-server-2abd97f5/bills/:billId", async (c) => {
  const user = await verifyAuth(c.req.header("Authorization"));
  
  if (!user) {
    return c.json({ error: "Unauthorized" }, 401);
  }

  const billId = c.req.param("billId");
  const body = await c.req.json();
  const bills = await kv.get(`bills:${user.id}`) || [];
  
  const billIndex = bills.findIndex((b: any) => b.id === billId);
  
  if (billIndex === -1) {
    return c.json({ error: "Bill not found" }, 404);
  }

  bills[billIndex] = { ...bills[billIndex], ...body, updatedAt: new Date().toISOString() };
  await kv.set(`bills:${user.id}`, bills);

  return c.json({ bill: bills[billIndex] });
});

// ============= PAYMENT ENDPOINTS =============

// Generate control number
app.post("/make-server-2abd97f5/payments/generate-control-number", async (c) => {
  const user = await verifyAuth(c.req.header("Authorization"));
  
  if (!user) {
    return c.json({ error: "Unauthorized" }, 401);
  }

  const body = await c.req.json();
  const { amount, billId, paymentMethod } = body;

  if (!amount) {
    return c.json({ error: "Amount is required" }, 400);
  }

  // Generate control number (mock implementation)
  const controlNumber = `CN${Math.random().toString(36).substring(2, 11).toUpperCase()}`;
  
  const payment = {
    id: `PAY-${Date.now()}`,
    userId: user.id,
    billId: billId || null,
    amount,
    paymentMethod: paymentMethod || "mobile",
    controlNumber,
    status: "pending",
    createdAt: new Date().toISOString(),
    expiresAt: new Date(Date.now() + 48 * 60 * 60 * 1000).toISOString(), // 48 hours
  };

  // Store payment
  const payments = await kv.get(`payments:${user.id}`) || [];
  payments.push(payment);
  await kv.set(`payments:${user.id}`, payments);

  return c.json({ 
    controlNumber,
    payment,
  });
});

// Get payment history
app.get("/make-server-2abd97f5/payments", async (c) => {
  const user = await verifyAuth(c.req.header("Authorization"));
  
  if (!user) {
    return c.json({ error: "Unauthorized" }, 401);
  }

  const payments = await kv.get(`payments:${user.id}`) || [];
  
  return c.json({ payments });
});

// ============= COMPLAINTS ENDPOINTS =============

// Get all complaints for a user
app.get("/make-server-2abd97f5/complaints", async (c) => {
  const user = await verifyAuth(c.req.header("Authorization"));
  
  if (!user) {
    return c.json({ error: "Unauthorized" }, 401);
  }

  const complaints = await kv.get(`complaints:${user.id}`) || [];
  
  return c.json({ complaints });
});

// Submit a new complaint
app.post("/make-server-2abd97f5/complaints", async (c) => {
  const user = await verifyAuth(c.req.header("Authorization"));
  
  if (!user) {
    return c.json({ error: "Unauthorized" }, 401);
  }

  const body = await c.req.json();
  const { type, description, location, contactMethod, urgency } = body;

  if (!type || !description) {
    return c.json({ error: "Type and description are required" }, 400);
  }

  const complaints = await kv.get(`complaints:${user.id}`) || [];
  
  const newComplaint = {
    id: `COMP-${new Date().getFullYear()}-${String(complaints.length + 1).padStart(3, "0")}`,
    userId: user.id,
    type,
    description,
    location: location || "",
    contactMethod: contactMethod || "phone",
    urgency: urgency || "medium",
    status: "Pending",
    response: "Complaint registered. Waiting for technician assignment.",
    createdAt: new Date().toISOString(),
  };

  complaints.push(newComplaint);
  await kv.set(`complaints:${user.id}`, complaints);

  return c.json({ complaint: newComplaint });
});

// Update complaint status
app.put("/make-server-2abd97f5/complaints/:complaintId", async (c) => {
  const user = await verifyAuth(c.req.header("Authorization"));
  
  if (!user) {
    return c.json({ error: "Unauthorized" }, 401);
  }

  const complaintId = c.req.param("complaintId");
  const body = await c.req.json();
  const complaints = await kv.get(`complaints:${user.id}`) || [];
  
  const complaintIndex = complaints.findIndex((comp: any) => comp.id === complaintId);
  
  if (complaintIndex === -1) {
    return c.json({ error: "Complaint not found" }, 404);
  }

  complaints[complaintIndex] = { 
    ...complaints[complaintIndex], 
    ...body, 
    updatedAt: new Date().toISOString() 
  };
  
  await kv.set(`complaints:${user.id}`, complaints);

  return c.json({ complaint: complaints[complaintIndex] });
});

Deno.serve(app.fetch);
