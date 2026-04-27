import { AuthProvider } from './contexts/AuthContext'
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import Layout from './components/Layout'
import LoginPage from './pages/LoginPage'
import DashboardPage from './pages/DashboardPage'
import RoomsPage from './pages/RoomsPage'
import BookingsPage from './pages/BookingsPage'
import BillingPage from './pages/BillingPage'
import StaffPage from './pages/StaffPage'
import TasksPage from './pages/TasksPage'
import InventoryPage from './pages/InventoryPage'
import CostsPage from './pages/CostsPage'
import ReportsPage from './pages/ReportsPage'
import InfrastructurePage from './pages/InfrastructurePage'
import MaintenancePage from './pages/MaintenancePage'
import UsersPage from './pages/UsersPage'
import SettingsPage from './pages/SettingsPage'
import ProfilePage from './pages/ProfilePage'

function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/" element={<Layout />}>
            <Route index element={<Navigate to="/dashboard" replace />} />
            <Route path="dashboard" element={<DashboardPage />} />
            <Route path="rooms" element={<RoomsPage />} />
            <Route path="bookings" element={<BookingsPage />} />
            <Route path="billing" element={<BillingPage />} />
            <Route path="staff" element={<StaffPage />} />
            <Route path="tasks" element={<TasksPage />} />
            <Route path="inventory" element={<InventoryPage />} />
            <Route path="costs" element={<CostsPage />} />
            <Route path="reports" element={<ReportsPage />} />
            <Route path="infrastructure" element={<InfrastructurePage />} />
            <Route path="maintenance" element={<MaintenancePage />} />
            <Route path="users" element={<UsersPage />} />
            <Route path="settings" element={<SettingsPage />} />
            <Route path="profile" element={<ProfilePage />} />
          </Route>
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  )
}

export default App
