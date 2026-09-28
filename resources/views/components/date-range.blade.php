@props([
    'label' => 'Date range',
    'from' => '2026-08-01',
    'to' => '2026-09-09',
])

@php $uid = \Illuminate\Support\Str::random(5); @endphp

<fieldset class="min-w-0">
    <legend class="mb-1 block text-xs font-medium text-neutral-600">{{ $label }}</legend>
    <div class="flex items-center gap-1.5">
        <label for="from-{{ $uid }}" class="sr-only">From date</label>
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 grid w-8 place-items-center text-neutral-400">
                <x-icon name="calendar" class="h-4 w-4" />
            </span>
            <input id="from-{{ $uid }}"
                   type="date"
                   value="{{ $from }}"
                   class="h-9 w-[150px] rounded-[8px] border border-neutral-300 bg-white pl-8 pr-2 text-sm tabular-nums text-neutral-800 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-600/30">
        </div>

        <span class="text-sm text-neutral-400" aria-hidden="true">–</span>

        <label for="to-{{ $uid }}" class="sr-only">To date</label>
        <input id="to-{{ $uid }}"
               type="date"
               value="{{ $to }}"
               class="h-9 w-[150px] rounded-[8px] border border-neutral-300 bg-white px-2 text-sm tabular-nums text-neutral-800 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-600/30">
    </div>
</fieldset>
