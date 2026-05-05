<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:patients.view')->only(['index', 'show']);
        $this->middleware('permission:patients.create')->only(['create', 'store']);
        $this->middleware('permission:patients.edit')->only(['edit', 'update']);
        $this->middleware('permission:patients.delete')->only(['destroy']);
    }

    public function index()
    {
        // Check if user can only view assigned patients
        if (!auth()->user()->can('patients.view_all') && auth()->user()->can('patients.view_assigned')) {
            // Logic for viewing only assigned patients
            // Patients assigned to current user (doctor/nurse)
        }

        return view('patients.index');
    }

    public function create()
    {
        return view('patients.create');
    }

    public function store(Request $request)
    {
        // Store patient logic
        return redirect()->route('patients.index');
    }

    public function show($id)
    {
        return view('patients.show');
    }

    public function edit($id)
    {
        return view('patients.edit');
    }

    public function update(Request $request, $id)
    {
        // Update patient logic
        return redirect()->route('patients.index');
    }

    public function destroy($id)
    {
        // Delete patient logic
        return redirect()->route('patients.index');
    }
}
