@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg shadow-sm border border-gray-200 ' . $class]) }}>
    @isset($title)
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
        </div>
    @endisset
    <div class="p-6">
        {{ $slot }}
    </div>
</div>
