<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:inventory.view')->only(['index', 'show']);
        $this->middleware('permission:inventory.create')->only(['create', 'store']);
        $this->middleware('permission:inventory.edit')->only(['edit', 'update']);
        $this->middleware('permission:inventory.delete')->only(['destroy']);
    }

    public function index()
    {
        return view('inventory.index');
    }

    public function create()
    {
        return view('inventory.create');
    }

    public function store(Request $request)
    {
        // Store item logic
        return redirect()->route('inventory.index');
    }

    public function show($id)
    {
        return view('inventory.show');
    }

    public function edit($id)
    {
        return view('inventory.edit');
    }

    public function update(Request $request, $id)
    {
        // Update item logic
        return redirect()->route('inventory.index');
    }

    public function destroy($id)
    {
        // Delete item logic
        return redirect()->route('inventory.index');
    }
}
