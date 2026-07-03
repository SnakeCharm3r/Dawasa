<x-app-layout>
    <x-page-header title="Add Product" subtitle="Create a new product in your inventory" />

    <div class="mb-4">
        <a href="{{ route('products.index') }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Back to Inventory</a>
    </div>

    <x-card>
        <form method="POST" action="{{ route('products.store') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="name" value="Product Name" />
                    <x-text-input id="name" name="name" value="{{ old('name') }}" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="sku" value="SKU" />
                    <x-text-input id="sku" name="sku" value="{{ old('sku') }}" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('sku')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="category_id" value="Category" />
                    <select id="category_id" name="category_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">Select a category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="stock_quantity" value="Stock Quantity" />
                    <x-text-input id="stock_quantity" name="stock_quantity" type="number" min="0" value="{{ old('stock_quantity', 0) }}" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('stock_quantity')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="price" value="Selling Price ($)" />
                    <x-text-input id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price') }}" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('price')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="cost" value="Cost Price ($)" />
                    <x-text-input id="cost" name="cost" type="number" step="0.01" min="0" value="{{ old('cost', 0) }}" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('cost')" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="description" value="Description (optional)" />
                    <textarea id="description" name="description" rows="3"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">{{ old('description') }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" checked
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                        <span class="text-sm text-gray-900">Active (available for sale)</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <x-button type="submit">Create Product</x-button>
                <a href="{{ route('products.index') }}">
                    <x-button type="button" variant="secondary">Cancel</x-button>
                </a>
            </div>
        </form>
    </x-card>
</x-app-layout>
