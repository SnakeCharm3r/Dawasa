<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();

        // Initialize data array
        $data = [
            'totalPatients' => 0,
            'todayAppointments' => 0,
            'availableBeds' => 150, // Total beds in hospital
            'emergencyCases' => 0,
            'totalStaff' => 0,
            'totalInventory' => 0,
            'todayRevenue' => '$0.00',
        ];

        // Get counts based on user permissions
        if ($user->can('users.view')) {
            $data['totalStaff'] = User::where('is_active', true)->count();
        }

        if ($user->can('patients.view')) {
            // TODO: Replace with actual Patient model count
            // $data['totalPatients'] = Patient::count();
            $data['totalPatients'] = 0;
        }

        if ($user->can('appointments.view')) {
            // TODO: Replace with actual Appointment model count for today
            // $data['todayAppointments'] = Appointment::whereDate('date', today())->count();
            $data['todayAppointments'] = 0;
        }

        if ($user->can('inventory.view')) {
            // TODO: Replace with actual Inventory model count
            // $data['totalInventory'] = Inventory::count();
            $data['totalInventory'] = 0;
        }

        if ($user->can('billing.view')) {
            // TODO: Replace with actual Billing calculation
            // $data['todayRevenue'] = Payment::whereDate('created_at', today())->sum('amount');
            $data['todayRevenue'] = '$0.00';
        }

        // Get user's role-specific dashboard view
        $dashboardView = $this->getRoleBasedDashboard($user);

        return view('dashboard.index', array_merge($data, ['dashboardView' => $dashboardView]));
    }

    /**
     * Get dashboard view based on user role
     */
    private function getRoleBasedDashboard($user)
    {
        if ($user->hasRole('System Admin')) {
            return 'admin';
        }

        if ($user->hasRole('Hospital Manager')) {
            return 'manager';
        }

        if ($user->hasRole('Doctor')) {
            return 'doctor';
        }

        if ($user->hasRole('Nurse')) {
            return 'nurse';
        }

        if ($user->hasRole('Cashier')) {
            return 'cashier';
        }

        return 'default';
    }
}
