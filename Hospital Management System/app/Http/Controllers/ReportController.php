<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Check which reports the user can view
        $canViewSales = auth()->user()->can('reports.view_sales');
        $canViewPatients = auth()->user()->can('reports.view_patients');
        $canViewStaff = auth()->user()->can('reports.view_staff');
        $canViewFinancial = auth()->user()->can('reports.view_financial');
        $canViewInventory = auth()->user()->can('reports.view_inventory');
        $canGenerate = auth()->user()->can('reports.generate');
        $canExport = auth()->user()->can('reports.export');

        return view('reports.index', compact(
            'canViewSales',
            'canViewPatients',
            'canViewStaff',
            'canViewFinancial',
            'canViewInventory',
            'canGenerate',
            'canExport'
        ));
    }

    public function generate(Request $request)
    {
        // Check specific report permission
        $reportType = $request->input('type');
        $permissionMap = [
            'sales' => 'reports.view_sales',
            'patients' => 'reports.view_patients',
            'staff' => 'reports.view_staff',
            'financial' => 'reports.view_financial',
            'inventory' => 'reports.view_inventory',
        ];

        if (isset($permissionMap[$reportType]) && !auth()->user()->can($permissionMap[$reportType])) {
            abort(403, 'Unauthorized to view this report type.');
        }

        // Check generate permission
        if (!auth()->user()->can('reports.generate')) {
            abort(403, 'Unauthorized to generate reports.');
        }

        // Report generation logic
        return response()->download('report.pdf');
    }
}
