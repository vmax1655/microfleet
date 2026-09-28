@props([
    'sortKey' => '',
    'sortDir' => 'asc',
    'caption' => null,
    'stack' => true,   // collapse into stacked cards under 768px
])

{{--
    Semantic <table>. Sorting is client-side over data-* attributes on each row,
    which keeps the markup identical whether rows come from mock arrays now or
    from a paginator later.

    Row contract:
      <tr data-row data-name="…" data-amount="12345">
        <td data-label="Member">…</td>
--}}
<div x-data="sortableTable(@js($sortKey), @js($sortDir))"
     data-table-root
     {{ $attributes->merge(['class' => 'w-full']) }}>
    <div class="{{ $stack ? 'md:overflow-x-auto' : 'overflow-x-auto' }}">
        <table class="w-full min-w-full border-collapse text-sm {{ $stack ? 'stack-table' : '' }}">
            @if($caption)
                <caption class="sr-only">{{ $caption }}</caption>
            @endif

            <thead class="bg-neutral-100">
                <tr>{{ $head }}</tr>
            </thead>

            <tbody class="divide-y divide-neutral-200">
                {{ $slot }}
            </tbody>

            @isset($foot)
                <tfoot class="border-t-2 border-neutral-300 bg-neutral-50 font-medium">
                    {{ $foot }}
                </tfoot>
            @endisset
        </table>
    </div>
</div>
