@props(['trail' => null])

@php
    // Falls back to the module tree so every sub-module page gets the right
    // "Home / Module / Sub-module" trail for free.
    $items = $trail ?? \App\Support\Nav::breadcrumb(request()->route()?->getName());
@endphp

<nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'mb-3']) }}>
    <ol class="flex flex-wrap items-center gap-1 text-[13px] text-neutral-500">
        <li>
            <a href="{{ route('dashboard') }}" wire:navigate class="rounded-[4px] hover:text-primary-700 hover:underline">Home</a>
        </li>

        @foreach($items as $i => $item)
            <li aria-hidden="true" class="text-neutral-300">
                <x-icon name="chevron-right" class="h-3.5 w-3.5" />
            </li>
            <li>
                @if($loop->last)
                    <span class="font-medium text-neutral-700" aria-current="page">{{ $item['label'] }}</span>
                @elseif(! empty($item['route']))
                    <a href="{{ route($item['route']) }}" wire:navigate class="rounded-[4px] hover:text-primary-700 hover:underline">{{ $item['label'] }}</a>
                @else
                    <span>{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
