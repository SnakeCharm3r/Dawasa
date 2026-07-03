<x-app-layout>
    <x-page-header title="Edit Permissions" :subtitle="'Assign permissions to ' . $user->name" />

    <div class="mb-4">
        <a href="{{ route('users.index') }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Back to Users</a>
    </div>

    <x-card>
        <form method="POST" action="{{ route('users.permissions.update', $user) }}">
            @csrf
            @method('PATCH')

            <div class="space-y-4">
                @foreach($availablePermissions as $value => $label)
                    <label class="flex items-center gap-3 p-3 rounded-md border border-gray-200 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="permissions[]" value="{{ $value }}"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                               {{ in_array($value, $user->permissions ?? []) ? 'checked' : '' }} />
                        <span class="text-sm text-gray-900">{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            <div class="mt-6 flex items-center gap-3">
                <x-button type="submit">Save Permissions</x-button>
                <a href="{{ route('users.index') }}">
                    <x-button type="button" variant="secondary">Cancel</x-button>
                </a>
            </div>
        </form>
    </x-card>
</x-app-layout>
