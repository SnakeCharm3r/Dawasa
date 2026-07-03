<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();

        $totalProducts = Product::count();
        $lowStockProducts = Product::where('stock_quantity', '<', 10)->where('is_active', true)->count();
        $todayIncome = LedgerEntry::where('entry_date', $today)->where('type', 'income')->sum('amount');
        $todayExpense = LedgerEntry::where('entry_date', $today)->where('type', 'expense')->sum('amount');
        $todayNet = $todayIncome - $todayExpense;

        $recentLedgerEntries = LedgerEntry::with('user')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $lowStockItems = Product::with('category')
            ->where('stock_quantity', '<', 10)
            ->where('is_active', true)
            ->orderBy('stock_quantity')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'totalProducts',
            'lowStockProducts',
            'todayIncome',
            'todayExpense',
            'todayNet',
            'recentLedgerEntries',
            'lowStockItems'
        ));
    }
}
