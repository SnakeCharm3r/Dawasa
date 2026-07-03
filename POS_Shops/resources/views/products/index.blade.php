<x-app-layout>
    @if(!empty($denied))
        <x-page-header title="Inventory" subtitle="Manage your products" />
        <div class="mt-6 rounded-lg border border-yellow-200 bg-yellow-50 p-6 flex items-start gap-4">
            <svg class="w-6 h-6 text-yellow-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div>
                <p class="font-semibold text-yellow-800">Access Restricted</p>
                <p class="text-sm text-yellow-700 mt-1">You do not have permission to view Inventory. Please contact your owner to request access.</p>
            </div>
        </div>
    @else
    <div class="flex items-center justify-between mb-6">
        <x-page-header title="Inventory" subtitle="Manage your products" />
        <x-button href="{{ route('products.create') }}">+ Add Product</x-button>
    </div>

    <div class="mb-4 flex flex-col sm:flex-row gap-3">
        <form method="GET" class="flex-1 flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or SKU..."
                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
            <select name="category" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
            <x-button type="submit" variant="secondary">Filter</x-button>
        </form>
    </div>

    <x-card>
        <x-data-table :headers="['Name', 'SKU', 'Category', 'Price', 'Stock', 'Status', 'Actions']">
            @foreach($products as $product)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $product->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $product->sku }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $product->category->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${{ number_format($product->price, 2) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($product->stock_quantity < 5)
                            <span class="text-red-600 font-medium">{{ $product->stock_quantity }}</span>
                        @elseif($product->stock_quantity < 10)
                            <span class="text-yellow-600 font-medium">{{ $product->stock_quantity }}</span>
                        @else
                            <span class="text-gray-900">{{ $product->stock_quantity }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($product->is_active)
                            <x-status-badge status="success">Active</x-status-badge>
                        @else
                            <x-status-badge status="default">Inactive</x-status-badge>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('products.edit', $product) }}" class="text-blue-600 hover:text-blue-800">Edit</a>
                            <span class="text-gray-300">|</span>
                            <form method="POST" action="{{ route('products.destroy', $product) }}" class="inline"
                                  onsubmit="return confirm('Delete this product?')">
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
            {{ $products->links() }}
        </div>
    </x-card>
    @endif
</x-app-layout>
