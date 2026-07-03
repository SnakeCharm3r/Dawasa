<x-app-layout>
    <x-page-header title="System Settings" subtitle="Configure branding and session behaviour" />

    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6 max-w-2xl">
        @csrf

        {{-- App Name --}}
        <x-card>
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Application Name</h3>
            <div>
                <label class="block text-sm text-gray-600 mb-1">Display Name</label>
                <input type="text" name="app_name" value="{{ old('app_name', $settings['app_name']) }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                       placeholder="SimplePOS" />
                @error('app_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </x-card>

        {{-- Favicon --}}
        <x-card>
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Favicon</h3>
            <div class="flex items-center gap-4">
                @if($settings['favicon'])
                    <img src="{{ Storage::url($settings['favicon']) }}" alt="Favicon" class="w-8 h-8 rounded object-contain border border-gray-200" />
                @else
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-sm">S</span>
                    </div>
                @endif
                <div class="flex-1">
                    <input type="file" name="favicon" accept=".png,.ico,.jpg,.jpeg,.svg"
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                    <p class="text-xs text-gray-400 mt-1">PNG, ICO, SVG — max 512KB. Shown in browser tab.</p>
                    @error('favicon') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-card>

        {{-- Profile / App Icon --}}
        <x-card>
            <h3 class="text-sm font-semibold text-gray-700 mb-4">App / Profile Icon</h3>
            <div class="flex items-center gap-4">
                @if($settings['profile_icon'])
                    <img src="{{ Storage::url($settings['profile_icon']) }}" alt="Profile Icon" class="w-10 h-10 rounded-full object-cover border border-gray-200" />
                @else
                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                        <x-heroicon-o-user class="w-6 h-6 text-gray-500" />
                    </div>
                @endif
                <div class="flex-1">
                    <input type="file" name="profile_icon" accept=".png,.jpg,.jpeg,.svg"
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                    <p class="text-xs text-gray-400 mt-1">PNG, JPG, SVG — max 1MB. Used as the top-right user avatar.</p>
                    @error('profile_icon') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-card>

        {{-- Idle Session Timeout --}}
        <x-card>
            <h3 class="text-sm font-semibold text-gray-700 mb-1">Idle Session Timeout</h3>
            <p class="text-xs text-gray-400 mb-4">Users will be automatically logged out after this many minutes of inactivity.</p>
            <div class="flex items-center gap-3">
                <input type="number" name="idle_timeout" value="{{ old('idle_timeout', $settings['idle_timeout']) }}"
                       min="1" max="480"
                       class="w-28 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                <span class="text-sm text-gray-500">minutes</span>
            </div>
            @error('idle_timeout') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </x-card>

        <div class="flex gap-3">
            <x-button type="submit">Save Settings</x-button>
        </div>
    </form>
</x-app-layout>
