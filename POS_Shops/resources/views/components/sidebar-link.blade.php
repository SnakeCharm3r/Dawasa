@props(['href' => null, 'active' => false, 'icon' => null])

@php
$activeClass = $active ? 'bg-blue-50 text-blue-700 border-blue-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 border-transparent';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-md border-l-4 transition-colors duration-150 ' . $activeClass]) }}>
    @if($icon)
        @svg($icon, 'w-5 h-5')
    @endif
    <span>{{ $slot }}</span>
</a>
