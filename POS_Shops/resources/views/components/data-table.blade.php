@props(['headers' => [], 'empty' => 'No data available'])

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        @if(!empty($headers))
            <thead class="bg-gray-50">
                <tr>
                    @foreach($headers as $header)
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ $header }}
                        </th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody class="bg-white divide-y divide-gray-200">
            @if($slot->isNotEmpty())
                {{ $slot }}
            @else
                <tr>
                    <td class="px-6 py-4 text-center text-sm text-gray-500" colspan="{{ count($headers) }}">
                        {{ $empty }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
