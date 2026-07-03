@php
$user = auth()->user();

$navItems = [
    ['route' => 'dashboard',       'label' => 'Dashboard',        'icon' => 'heroicon-o-home'],
    ['route' => 'products.index',  'label' => 'Inventory',         'icon' => 'heroicon-o-cube'],
    ['route' => 'categories.index','label' => 'Categories',        'icon' => 'heroicon-o-tag'],
    ['route' => 'ledger.index',    'label' => 'Daily Ledger',      'icon' => 'heroicon-o-clipboard-document-list'],
];

if ($user->isOwner()) {
    array_splice($navItems, 1, 0, [
        ['route' => 'users.index', 'label' => 'User Management', 'icon' => 'heroicon-o-users'],
    ]);
}
@endphp

<aside class="w-64 bg-white border-r border-gray-200 flex flex-col shrink-0 hidden md:flex">
    <div class="h-16 flex items-center px-6 border-b border-gray-200">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold text-sm">S</span>
            </div>
            <span class="font-bold text-lg text-gray-900">SimplePOS</span>
        </a>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        @foreach($navItems as $item)
            @php
            $isActive = request()->routeIs($item['route']) || ($item['route'] === 'products.index' && request()->routeIs('products.*'));
            @endphp
            <x-sidebar-link :href="route($item['route'])" :active="$isActive" :icon="$item['icon']">
                {{ $item['label'] }}
            </x-sidebar-link>
        @endforeach
    </nav>

    <div class="px-3 py-4 border-t border-gray-200 space-y-1">
        @if($user->isOwner())
            <x-sidebar-link :href="route('settings.index')" :active="request()->routeIs('settings.*')" icon="heroicon-o-cog-6-tooth">
                Settings
            </x-sidebar-link>
        @endif
        <div class="px-3 py-2">
            <p class="text-xs text-gray-500">Signed in as</p>
            <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
            <p class="text-xs text-gray-500 capitalize">{{ auth()->user()->role }}</p>
        </div>
    </div>
</aside>

<div x-data="{ sidebarOpen: false }" class="md:hidden">
    <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 bg-black/50" @click="sidebarOpen = false"></div>
    <aside x-show="sidebarOpen" x-cloak
           x-transition:enter="transition ease-in-out duration-200"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in-out duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full"
           class="fixed left-0 top-0 bottom-0 w-64 bg-white border-r border-gray-200 z-50 flex flex-col">
        <div class="h-16 flex items-center justify-between px-6 border-b border-gray-200">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2" @click="sidebarOpen = false">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-sm">S</span>
                </div>
                <span class="font-bold text-lg text-gray-900">SimplePOS</span>
            </a>
            <button @click="sidebarOpen = false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            @foreach($navItems as $item)
                @php
                $isActive = request()->routeIs($item['route']) || ($item['route'] === 'products.index' && request()->routeIs('products.*'));
                @endphp
                <x-sidebar-link :href="route($item['route'])" :active="$isActive" :icon="$item['icon']" @click="sidebarOpen = false">
                    {{ $item['label'] }}
                </x-sidebar-link>
            @endforeach
        </nav>
        @if($user->isOwner())
            <div class="px-3 py-3 border-t border-gray-200">
                <x-sidebar-link :href="route('settings.index')" :active="request()->routeIs('settings.*')" icon="heroicon-o-cog-6-tooth" @click="sidebarOpen = false">
                    Settings
                </x-sidebar-link>
            </div>
        @endif
    </aside>
</div>
