// Development environment configuration
export const isDevelopment = (import.meta as any).env?.DEV || true;

// API configuration that switches between mock and real APIs
export const API_CONFIG = {
  // Use mock data in development, real API in production
  useMock: isDevelopment,
  
  // Real API endpoints
  production: {
    baseUrl: `https://${(import.meta as any).env?.VITE_SUPABASE_PROJECT_ID || 'jtwsfbvushgthaurupfb'}.supabase.co/functions/v1/make-server-2abd97f5`,
    supabaseUrl: `https://${(import.meta as any).env?.VITE_SUPABASE_PROJECT_ID || 'jtwsfbvushgthaurupfb'}.supabase.co`,
    supabaseAnonKey: (import.meta as any).env?.VITE_SUPABASE_ANON_KEY || 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imp0d3NmYnZ1c2hndGhhdXJ1cGZiIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzMwMDcxMjgsImV4cCI6MjA4ODU4MzEyOH0.J2l_YswU6ze3jZT_TUvvKiCle6Qxr4Nzz_RP7m_U1fI'
  }
};

// Test credentials for development
export const TEST_CREDENTIALS = {
  valid: [
    {
      ipNumber: "192.168.1.100",
      password: "test123",
      user: {
        id: "1",
        firstName: "Test",
        lastName: "User",
        location: "Kinondoni, Dar es Salaam"
      }
    },
    {
      ipNumber: "192.168.1.101", 
      password: "demo123",
      user: {
        id: "2",
        firstName: "Demo",
        lastName: "Customer", 
        location: "Ilala, Dar es Salaam"
      }
    }
  ],
  invalid: [
    { ipNumber: "192.168.1.999", password: "wrong123" },
    { ipNumber: "invalid-ip", password: "test123" },
    { ipNumber: "192.168.1.100", password: "" }
  ]
};
