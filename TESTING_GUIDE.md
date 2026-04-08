# Local Development Testing Guide

## Environment Setup

The Dawasa app now supports **local development testing** with mock data and validation.

## Test Credentials

### Valid Test Users
1. **Test User 1**
   - IP Number: `192.168.1.100`
   - Password: `test123`
   - Name: Test User (Kinondoni, Dar es Salaam)

2. **Demo User 2**
   - IP Number: `192.168.1.101`
   - Password: `demo123`
   - Name: Demo Customer (Ilala, Dar es Salaam)

### Invalid Test Cases
- IP: `192.168.1.999`, Pass: `wrong123` (Invalid credentials)
- IP: `invalid-ip`, Pass: `test123` (Invalid IP format)
- IP: `192.168.1.100`, Pass: `` (Empty password)

## Features

### 1. Mock Authentication
- Simulates real login/signup flow
- Token storage in localStorage
- Session validation
- Automatic logout

### 2. Mock Data
- **Bills**: Sample water bills with different statuses
- **Payments**: Payment history and control number generation
- **Complaints**: Sample complaints and submission

### 3. Development UI
- Test credentials panel (visible in dev mode only)
- Auto-fill button for quick testing
- Visual indicators for dev environment

## Testing Workflow

1. **Start Development Server**
   ```bash
   npm run dev
   ```

2. **Test Login**
   - Open browser to `http://localhost:5173`
   - Click "Auto-fill Test Credentials" or enter manually
   - Verify successful login and dashboard redirect

3. **Test Features**
   - Dashboard: View bills, user info, statistics
   - Bills Page: Check bill history and status
   - Payment Page: Generate control numbers
   - Complaints Page: Submit and view complaints

4. **Test Validation**
   - Try invalid credentials
   - Test empty form submissions
   - Verify error messages

## Configuration

### Environment Detection
- Automatically uses mock data in development
- Switches to real API in production
- Configurable via `API_CONFIG.useMock`

### Environment Variables (Optional)
```bash
VITE_SUPABASE_PROJECT_ID=your_project_id
VITE_SUPABASE_ANON_KEY=your_anon_key
```

## Mock Data Structure

### User Data
```javascript
{
  id: "1",
  firstName: "Test",
  lastName: "User", 
  location: "Kinondoni, Dar es Salaam",
  ipNumber: "192.168.1.100"
}
```

### Bill Data
```javascript
{
  id: "1",
  month: "January 2026",
  dueDate: "2026-01-31",
  amount: 45000,
  status: "Paid|Pending",
  meterReading: "1234",
  units: 45
}
```

## Production Deployment

When deploying to production:
1. Set `API_CONFIG.useMock = false`
2. Configure environment variables
3. Mock data and test credentials will be hidden
4. Real Supabase API will be used

This setup provides a complete development environment for testing all app features without requiring backend setup.
