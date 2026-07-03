<x-app-layout>
    <x-page-header title="Edit Category" :subtitle="$category->name" />

    <div class="mb-4">
        <a href="{{ route('categories.index') }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Back to Categories</a>
    </div>

    <x-card>
        <form method="POST" action="{{ route('categories.update', $category) }}">
            @csrf
            @method('PATCH')

            <div class="space-y-4">
                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" value="{{ old('name', $category->name) }}" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="description" value="Description (optional)" />
                    <textarea id="description" name="description" rows="3"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">{{ old('description', $category->description) }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <x-button type="submit">Update Category</x-button>
                <a href="{{ route('categories.index') }}">
                    <x-button type="button" variant="secondary">Cancel</x-button>
                </a>
            </div>
        </form>
    </x-card>
</x-app-layout>
