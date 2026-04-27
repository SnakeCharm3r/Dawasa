import { Outlet, NavLink, useNavigate } from 'react-router-dom'
import {
  LayoutDashboard, BedDouble, CalendarCheck, Receipt,
  Users, ClipboardList, Package, DollarSign, BarChart3,
  Wifi, Wrench, Settings, UserCircle, LogOut, ShieldCheck,
} from 'lucide-react'
import { useAuth } from '../contexts/AuthContext'

const navItems = [
  { to: '/dashboard', icon: LayoutDashboard, label: 'Dashboard' },
  { to: '/rooms', icon: BedDouble, label: 'Rooms' },
  { to: '/bookings', icon: CalendarCheck, label: 'Bookings' },
  { to: '/billing', icon: Receipt, label: 'Billing' },
  { to: '/staff', icon: Users, label: 'Staff' },
  { to: '/tasks', icon: ClipboardList, label: 'Tasks' },
  { to: '/inventory', icon: Package, label: 'Inventory' },
  { to: '/costs', icon: DollarSign, label: 'Costs' },
  { to: '/reports', icon: BarChart3, label: 'Reports' },
  { to: '/infrastructure', icon: Wifi, label: 'Infrastructure' },
  { to: '/maintenance', icon: Wrench, label: 'Maintenance' },
  { to: '/users', icon: ShieldCheck, label: 'Users' },
  { to: '/settings', icon: Settings, label: 'Settings' },
]

export default function Layout() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  const handleLogout = () => {
    logout()
    navigate('/login')
  }

  return (
    <div className="flex h-screen bg-gray-100">
      <aside className="w-64 bg-gray-900 text-white flex flex-col">
        <div className="px-6 py-5 border-b border-gray-700">
          <h1 className="text-xl font-bold text-white">Lodge POS</h1>
          {user && (
            <p className="text-xs text-gray-400 mt-1 capitalize">{user.role}</p>
          )}
        </div>
        <nav className="flex-1 overflow-y-auto py-4">
          {navItems.map(({ to, icon: Icon, label }) => (
            <NavLink
              key={to}
              to={to}
              className={({ isActive }) =>
                `flex items-center gap-3 px-6 py-2.5 text-sm transition-colors ${
                  isActive
                    ? 'bg-blue-600 text-white'
                    : 'text-gray-300 hover:bg-gray-800 hover:text-white'
                }`
              }
            >
              <Icon size={18} />
              {label}
            </NavLink>
          ))}
        </nav>
        <div className="border-t border-gray-700 px-4 py-3 flex items-center gap-3">
          <NavLink
            to="/profile"
            className="flex items-center gap-2 text-gray-300 hover:text-white text-sm flex-1"
          >
            <UserCircle size={18} />
            {user?.name ?? 'Profile'}
          </NavLink>
          <button
            onClick={handleLogout}
            className="text-gray-400 hover:text-red-400 transition-colors"
            title="Logout"
          >
            <LogOut size={18} />
          </button>
        </div>
      </aside>
      <main className="flex-1 overflow-auto">
        <Outlet />
      </main>
    </div>
  )
}
