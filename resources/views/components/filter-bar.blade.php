@props([
    'searchPlaceholder' => 'Search…',
    'searchLabel' => 'Search',
    'searchId' => null,
    'dateRange' => false,
    'export' => true,
    'showSearch' => true,
])

@php $searchId = $searchId ?? 'filter-search-'.\Illuminate\Support\Str::random(5); @endphp

<section x-data="filterBar()"
         {{ $attributes->merge(['class' => 'mb-4 rounded-[12px] border border-neutral-200 bg-white p-4 shadow-card']) }}>
    <form class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between" onsubmit="return false;">

        {{-- Left: search --}}
        @if($showSearch)
            <div class="min-w-0 lg:max-w-sm lg:flex-1">
                <label for="{{ $searchId }}" class="mb-1 block text-xs font-medium text-neutral-600">{{ $searchLabel }}</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 grid w-9 place-items-center text-neutral-400">
                        <x-icon name="search" />
                    </span>
                    <input id="{{ $searchId }}"
                           type="search"
                           x-model.debounce.150ms="query"
                           @input="filterRows()"
                           placeholder="{{ $searchPlaceholder }}"
                           class="h-9 w-full rounded-[8px] border border-neutral-300 bg-white pl-9 pr-3 text-sm text-neutral-800 placeholder:text-neutral-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-600/30">
                </div>
            </div>
        @endif

        {{-- Right: date range + selects + export --}}
        <div class="flex flex-wrap items-end gap-3">
            @if($dateRange)
                <x-date-range />
            @endif

            {{ $slot }}

            @if($export)
                <div x-data="{ open: false }" class="relative" @keydown.escape="open = false">
                    <x-btn @click="open = !open" icon="download" ::aria-expanded="open ? 'true' : 'false'">Export</x-btn>
                    <div x-show="open"
                         x-cloak
                         x-transition.opacity.duration.150ms
                         @click.outside="open = false"
                         class="absolute right-0 z-30 mt-1.5 w-44 rounded-[12px] border border-neutral-200 bg-white p-1.5 shadow-flyout">
                        <button type="button" class="flex w-full items-center gap-2.5 rounded-[8px] px-2.5 py-2 text-sm text-neutral-700 hover:bg-neutral-50">
                            <x-icon name="file-spreadsheet" class="text-neutral-500" /> Export to Excel
                        </button>
                        <button type="button" class="flex w-full items-center gap-2.5 rounded-[8px] px-2.5 py-2 text-sm text-neutral-700 hover:bg-neutral-50">
                            <x-icon name="file-text" class="text-neutral-500" /> Export to PDF
                        </button>
                        <button type="button" class="flex w-full items-center gap-2.5 rounded-[8px] px-2.5 py-2 text-sm text-neutral-700 hover:bg-neutral-50">
                            <x-icon name="printer" class="text-neutral-500" /> Print
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </form>
</section>
