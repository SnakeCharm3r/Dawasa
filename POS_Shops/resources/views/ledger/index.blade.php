<x-app-layout>
    @if(!empty($denied))
        <x-page-header title="Daily Ledger" subtitle="" />
        <div class="mt-6 rounded-lg border border-yellow-200 bg-yellow-50 p-6 flex items-start gap-4">
            <svg class="w-6 h-6 text-yellow-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div>
                <p class="font-semibold text-yellow-800">Access Restricted</p>
                <p class="text-sm text-yellow-700 mt-1">You do not have permission to view the Daily Ledger. Please contact your owner to request access.</p>
            </div>
        </div>
    @else
    <div class="flex items-center justify-between mb-6">
        <x-page-header title="Daily Ledger" subtitle="{{ $date->format('l, F j, Y') }}" />
        <x-button href="{{ route('ledger.create') }}">+ Add Entry</x-button>
    </div>

    <div class="mb-4">
        <form method="GET" class="flex gap-3 items-center">
            <label class="text-sm text-gray-500">Date:</label>
            <input type="date" name="date" value="{{ $date->format('Y-m-d') }}"
                   class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
            <x-button type="submit" variant="secondary">Go</x-button>
            <a href="{{ route('ledger.index', ['date' => today()->format('Y-m-d')]) }}">
                <x-button type="button" variant="secondary">Today</x-button>
            </a>
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-card>
            <p class="text-sm font-medium text-gray-500">Total Income</p>
            <p class="mt-1 text-2xl font-bold text-green-600">${{ number_format($totalIncome, 2) }}</p>
        </x-card>
        <x-card>
            <p class="text-sm font-medium text-gray-500">Total Expense</p>
            <p class="mt-1 text-2xl font-bold text-red-600">${{ number_format($totalExpense, 2) }}</p>
        </x-card>
        <x-card>
            <p class="text-sm font-medium text-gray-500">Net Total</p>
            <p class="mt-1 text-2xl font-bold {{ $netTotal >= 0 ? 'text-green-600' : 'text-red-600' }}">${{ number_format($netTotal, 2) }}</p>
        </x-card>
    </div>

    <x-card>
        <x-data-table :headers="['Type', 'Description', 'Amount', 'Recorded By', 'Actions']">
            @foreach($entries as $entry)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($entry->type === 'income')
                            <x-status-badge status="success">Income</x-status-badge>
                        @else
                            <x-status-badge status="danger">Expense</x-status-badge>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $entry->description }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold {{ $entry->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                        {{ $entry->type === 'income' ? '+' : '-' }}${{ number_format($entry->amount, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $entry->user->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <form method="POST" action="{{ route('ledger.destroy', $entry) }}" class="inline"
                              onsubmit="return confirm('Delete this entry?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </x-data-table>
    </x-card>
    @endif
</x-app-layout>
