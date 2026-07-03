@props(['href' => null, 'variant' => 'primary', 'type' => 'button', 'class' => ''])

@php
$variants = [
    'primary' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
    'secondary' => 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 focus:ring-gray-500',
    'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
    'warning' => 'bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500',
];
$variantClass = $variants[$variant] ?? $variants['primary'];
$baseClass = 'inline-flex items-center px-4 py-2 border border-transparent rounded-md font-medium text-sm transition ease-in-out duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 ' . $variantClass;
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $baseClass . ' ' . $class]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $baseClass . ' ' . $class]) }}>
        {{ $slot }}
    </button>
@endif
