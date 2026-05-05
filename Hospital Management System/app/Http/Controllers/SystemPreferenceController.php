<?php

namespace App\Http\Controllers;

use App\Models\SystemPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SystemPreferenceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:system_preferences.view')->only(['index']);
        $this->middleware('permission:system_preferences.edit')->only(['update']);
        $this->middleware('permission:system_preferences.manage_logo')->only(['updateLogo']);
        $this->middleware('permission:system_preferences.manage_favicon')->only(['updateFavicon']);
    }

    /**
     * Show system preferences
     */
    public function index()
    {
        $preferences = SystemPreference::getInstance();
        return view('system-preferences.index', compact('preferences'));
    }

    /**
     * Update system preferences
     */
    public function update(Request $request)
    {
        $request->validate([
            'hospital_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string'],
            'timezone' => ['required', 'string', 'max:50'],
            'date_format' => ['required', 'string', 'max:20'],
            'currency' => ['required', 'string', 'max:10'],
            'items_per_page' => ['required', 'integer', 'min:5', 'max:100'],
            'maintenance_mode' => ['boolean'],
        ]);

        $preferences = SystemPreference::getInstance();

        $preferences->update([
            'hospital_name' => $request->hospital_name,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
            'website' => $request->website,
            'description' => $request->description,
            'timezone' => $request->timezone,
            'date_format' => $request->date_format,
            'currency' => $request->currency,
            'items_per_page' => $request->items_per_page,
            'maintenance_mode' => $request->boolean('maintenance_mode'),
        ]);

        return redirect()->route('system-preferences.index')
            ->with('success', 'System preferences updated successfully.');
    }

    /**
     * Update logo
     */
    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ]);

        $preferences = SystemPreference::getInstance();

        // Delete old logo if exists
        if ($preferences->logo_path) {
            Storage::disk('public')->delete($preferences->logo_path);
        }

        // Store new logo
        $path = $request->file('logo')->store('logos', 'public');
        $preferences->update(['logo_path' => $path]);

        return redirect()->route('system-preferences.index')
            ->with('success', 'Logo updated successfully.');
    }

    /**
     * Update favicon
     */
    public function updateFavicon(Request $request)
    {
        $request->validate([
            'favicon' => ['required', 'image', 'mimes:ico,png,jpg,svg', 'max:1024'],
        ]);

        $preferences = SystemPreference::getInstance();

        // Delete old favicon if exists
        if ($preferences->favicon_path) {
            Storage::disk('public')->delete($preferences->favicon_path);
        }

        // Store new favicon
        $path = $request->file('favicon')->store('favicons', 'public');
        $preferences->update(['favicon_path' => $path]);

        return redirect()->route('system-preferences.index')
            ->with('success', 'Favicon updated successfully.');
    }

    /**
     * Get public system info (API endpoint)
     */
    public function publicInfo()
    {
        $preferences = SystemPreference::getInstance();

        return response()->json([
            'hospital_name' => $preferences->hospital_name,
            'logo_url' => $preferences->logo_path ? Storage::url($preferences->logo_path) : null,
            'favicon_url' => $preferences->favicon_path ? Storage::url($preferences->favicon_path) : null,
            'address' => $preferences->address,
            'phone' => $preferences->phone,
            'email' => $preferences->email,
            'website' => $preferences->website,
            'description' => $preferences->description,
            'currency' => $preferences->currency,
        ]);
    }
}
