@props([
    'sort' => null,      // data-* key on <tr>, e.g. "amount" for data-amount
    'align' => 'left',   // left | right | center
    'width' => null,
    'srOnly' => false,
])

@php
    $alignClass = ['left' => 'text-left', 'right' => 'text-right', 'center' => 'text-center'][$align] ?? 'text-left';
    $base = 'whitespace-nowrap px-3 py-2.5 text-xs font-semibold uppercase tracking-wide text-neutral-600 '.$alignClass;
@endphp

<th scope="col"
    @if($sort) :aria-sort="ariaSort(@js($sort))" @endif
    @if($width) style="width: {{ $width }}" @endif
    {{ $attributes->merge(['class' => $base]) }}>

    @if($srOnly)
        <span class="sr-only">{{ $slot }}</span>
    @elseif($sort)
        <button type="button"
                @click="sort(@js($sort))"
                class="group -mx-1 inline-flex items-center gap-1 rounded-[4px] px-1 py-0.5 uppercase hover:text-neutral-900 {{ $align === 'right' ? 'flex-row-reverse' : '' }}">
            <span>{{ $slot }}</span>
            <span class="text-neutral-400 group-hover:text-neutral-600"
                  :class="sortKey === @js($sort) ? 'text-primary-700' : ''">
                <template x-if="sortKey !== @js($sort)">
                    <span><x-icon name="chevrons-up-down" class="h-3.5 w-3.5" /></span>
                </template>
                <template x-if="sortKey === @js($sort) && sortDir === 'asc'">
                    <span><x-icon name="arrow-up" class="h-3.5 w-3.5" stroke="2.25" /></span>
                </template>
                <template x-if="sortKey === @js($sort) && sortDir === 'desc'">
                    <span><x-icon name="arrow-down" class="h-3.5 w-3.5" stroke="2.25" /></span>
                </template>
            </span>
        </button>
    @else
        {{ $slot }}
    @endif
</th>
