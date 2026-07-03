<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'app_name'            => Setting::get('app_name', config('app.name')),
            'favicon'             => Setting::get('favicon'),
            'profile_icon'        => Setting::get('profile_icon'),
            'idle_timeout'        => Setting::get('idle_timeout', 30),
        ];

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_name'     => 'nullable|string|max:100',
            'favicon'      => 'nullable|image|mimes:png,ico,jpg,jpeg,svg|max:512',
            'profile_icon' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:1024',
            'idle_timeout' => 'required|integer|min:1|max:480',
        ]);

        if ($request->hasFile('favicon')) {
            $path = $request->file('favicon')->store('settings', 'public');
            Setting::set('favicon', $path);
        }

        if ($request->hasFile('profile_icon')) {
            $path = $request->file('profile_icon')->store('settings', 'public');
            Setting::set('profile_icon', $path);
        }

        if (!empty($validated['app_name'])) {
            Setting::set('app_name', $validated['app_name']);
        }

        Setting::set('idle_timeout', $validated['idle_timeout']);

        return redirect()->route('settings.index')->with('success', 'Settings saved successfully.');
    }
}
