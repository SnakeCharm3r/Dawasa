<x-app-layout>
    <x-page-header title="Dashboard" subtitle="Overview of your business today" />

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Products</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ $totalProducts }}</p>
                </div>
                <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Low Stock Items</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ $lowStockProducts }}</p>
                </div>
                <div class="w-10 h-10 bg-yellow-50 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Today's Income</p>
                    <p class="mt-1 text-2xl font-bold text-green-600">${{ number_format($todayIncome, 2) }}</p>
                </div>
                <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                    </svg>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Today's Net</p>
                    <p class="mt-1 text-2xl font-bold {{ $todayNet >= 0 ? 'text-green-600' : 'text-red-600' }}">${{ number_format($todayNet, 2) }}</p>
                </div>
                <div class="w-10 h-10 {{ $todayNet >= 0 ? 'bg-green-50' : 'bg-red-50' }} rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 {{ $todayNet >= 0 ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 14h.01M12 17h.01M16 3h5v5M8 3H3v5m5 14H3v-5m13 5h5v-5"/>
                    </svg>
                </div>
            </div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-card title="Recent Ledger Entries">
            @if($recentLedgerEntries->isNotEmpty())
                <div class="space-y-3">
                    @foreach($recentLedgerEntries as $entry)
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $entry->description }}</p>
                                <p class="text-xs text-gray-500">{{ $entry->user->name }} - {{ $entry->entry_date->format('M j, Y') }}</p>
                            </div>
                            <span class="text-sm font-semibold {{ $entry->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $entry->type === 'income' ? '+' : '-' }}${{ number_format($entry->amount, 2) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 text-center py-4">No recent entries.</p>
            @endif
        </x-card>

        <x-card title="Low Stock Alert">
            @if($lowStockItems->isNotEmpty())
                <div class="space-y-3">
                    @foreach($lowStockItems as $item)
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $item->name }}</p>
                                <p class="text-xs text-gray-500">{{ $item->category->name }} - SKU: {{ $item->sku }}</p>
                            </div>
                            <x-status-badge status="{{ $item->stock_quantity < 5 ? 'danger' : 'warning' }}">
                                {{ $item->stock_quantity }} left
                            </x-status-badge>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 text-center py-4">All products are well stocked.</p>
            @endif
        </x-card>
    </div>
</x-app-layout>
