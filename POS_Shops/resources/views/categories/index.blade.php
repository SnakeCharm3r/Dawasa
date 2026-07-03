<x-app-layout>
    @if(!empty($denied))
        <x-page-header title="Categories" subtitle="Organize your products into categories" />
        <div class="mt-6 rounded-lg border border-yellow-200 bg-yellow-50 p-6 flex items-start gap-4">
            <svg class="w-6 h-6 text-yellow-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div>
                <p class="font-semibold text-yellow-800">Access Restricted</p>
                <p class="text-sm text-yellow-700 mt-1">You do not have permission to view Categories. Please contact your owner to request access.</p>
            </div>
        </div>
    @else
    <div class="flex items-center justify-between mb-6">
        <x-page-header title="Categories" subtitle="Organize your products into categories" />
        <x-button href="{{ route('categories.create') }}">+ Add Category</x-button>
    </div>

    <div class="mb-4">
        <form method="GET" class="flex gap-3 max-w-sm">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search categories..."
                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
            <x-button type="submit" variant="secondary">Search</x-button>
        </form>
    </div>

    <x-card>
        <x-data-table :headers="['Name', 'Description', 'Products', 'Actions']">
            @foreach($categories as $category)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $category->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $category->description ?? '—' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $category->products_count }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('categories.edit', $category) }}" class="text-blue-600 hover:text-blue-800">Edit</a>
                            <span class="text-gray-300">|</span>
                            <form method="POST" action="{{ route('categories.destroy', $category) }}" class="inline"
                                  onsubmit="return confirm('Delete this category?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-data-table>

        <div class="mt-4">
            {{ $categories->links() }}
        </div>
    </x-card>
    @endif
</x-app-layout>
