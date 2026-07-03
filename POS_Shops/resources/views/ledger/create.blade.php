<x-app-layout>
    <x-page-header title="Add Ledger Entry" subtitle="Record an income or expense" />

    <div class="mb-4">
        <a href="{{ route('ledger.index') }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Back to Ledger</a>
    </div>

    <x-card>
        <form method="POST" action="{{ route('ledger.store') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="entry_date" value="Date" />
                    <x-text-input id="entry_date" name="entry_date" type="date" value="{{ old('entry_date', today()->format('Y-m-d')) }}" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('entry_date')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="type" value="Type" />
                    <select id="type" name="type" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">Select type</option>
                        <option value="income" {{ old('type') === 'income' ? 'selected' : '' }}>Income</option>
                        <option value="expense" {{ old('type') === 'expense' ? 'selected' : '' }}>Expense</option>
                    </select>
                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="amount" value="Amount ($)" />
                    <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="description" value="Description" />
                    <x-text-input id="description" name="description" value="{{ old('description') }}" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <x-button type="submit">Add Entry</x-button>
                <a href="{{ route('ledger.index') }}">
                    <x-button type="button" variant="secondary">Cancel</x-button>
                </a>
            </div>
        </form>
    </x-card>
</x-app-layout>
