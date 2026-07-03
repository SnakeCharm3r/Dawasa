<?php

namespace App\Http\Controllers;

use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LedgerController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('view_ledger')) {
            return view('ledger.index', [
                'denied' => true,
                'entries' => collect(),
                'date' => Carbon::today(),
                'totalIncome' => 0,
                'totalExpense' => 0,
                'netTotal' => 0,
            ]);
        }

        $date = $request->filled('date') ? Carbon::parse($request->date) : Carbon::today();

        $entries = LedgerEntry::with('user')
            ->where('entry_date', $date)
            ->orderBy('created_at')
            ->get();

        $totalIncome = $entries->where('type', 'income')->sum('amount');
        $totalExpense = $entries->where('type', 'expense')->sum('amount');
        $netTotal = $totalIncome - $totalExpense;

        return view('ledger.index', compact('entries', 'date', 'totalIncome', 'totalExpense', 'netTotal'));
    }

    public function create()
    {
        return view('ledger.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'entry_date' => 'required|date',
            'type' => 'required|in:income,expense',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $validated['user_id'] = auth()->id();

        LedgerEntry::create($validated);

        return redirect()->route('ledger.index', ['date' => $validated['entry_date']])
            ->with('success', 'Ledger entry added successfully.');
    }

    public function destroy(LedgerEntry $ledgerEntry)
    {
        $date = $ledgerEntry->entry_date;
        $ledgerEntry->delete();

        return redirect()->route('ledger.index', ['date' => $date->format('Y-m-d')])
            ->with('success', 'Ledger entry deleted successfully.');
    }
}
