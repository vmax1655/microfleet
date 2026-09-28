@php
    use App\Support\Format;

    $trail = [
        ['label' => 'Membership', 'route' => 'membership.directory'],
        ['label' => 'Membership Reports', 'route' => 'membership.reports'],
        ['label' => $card['title']],
    ];

    $netNew = array_sum($growth['new']);
    $netExits = array_sum($growth['exits']);
@endphp

<div>
    <x-breadcrumb :trail="$trail" />

    <x-page-header
        :title="$card['title']"
        subtitle="Oct 2025 – Sep 2026 · Malolos Main Branch · generated {{ Format::dateTime('2026-09-09 08:20') }}"
        :back="route('membership.reports')"
        back-label="Reports">
        <x-slot:actions>
            <x-btn icon="printer">Print</x-btn>
            <x-btn icon="file-spreadsheet">Excel</x-btn>
            <x-btn variant="primary" icon="download">Export to PDF</x-btn>
        </x-slot:actions>
    </x-page-header>

    <x-filter-bar :show-search="false" :export="false" date-range>
        <x-select label="Branch" :options="['All branches', 'Malolos Main Branch', 'Sta. Maria Branch', 'San Jose del Monte Branch']" width="w-52" />
        <x-select label="Center" :options="collect($centers)->pluck('name')->all()" placeholder="All centers" width="w-52" />
        <x-select label="Group by" :options="['Month', 'Quarter', 'Center', 'Loan officer']" width="w-36" />
        <x-btn icon="refresh-cw">Run report</x-btn>
    </x-filter-bar>

    <section aria-label="Report summary" class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="New Members" :value="number_format($netNew)" :delta="8.4" icon="user-plus" />
        <x-stat-card label="Members Exited" :value="number_format($netExits)" :delta="3.1" good-direction="down" icon="log-out" />
        <x-stat-card label="Net Growth" :value="'+'.number_format($netNew - $netExits)" :delta="9.7" icon="trending-up" />
        <x-stat-card label="12-Month Retention" value="87.2%" :delta="1.4" icon="shield-check" />
    </section>

    <x-card class="mb-5" title="New members vs exits" subtitle="Monthly movement over the reporting period">
        <x-charts.member-growth height="h-80" />
    </x-card>

    <x-card flush title="Detail" subtitle="Month-by-month breakdown with running total">
        <x-data-table sort-key="month" caption="Member growth detail by month">
            <x-slot:head>
                <x-th sort="month">Month</x-th>
                <x-th sort="new" align="right">New Members</x-th>
                <x-th sort="exits" align="right">Exits</x-th>
                <x-th sort="net" align="right">Net Change</x-th>
                <x-th sort="running" align="right">Running Total</x-th>
                <x-th sort="rate" align="right">Growth Rate</x-th>
            </x-slot:head>

            @php $running = 1_020; @endphp
            @foreach($growth['labels'] as $i => $month)
                @php
                    $new = $growth['new'][$i];
                    $exits = $growth['exits'][$i];
                    $net = $new - $exits;
                    $rate = $running > 0 ? $net / $running * 100 : 0;
                    $running += $net;
                @endphp
                <tr data-row data-month="{{ $month }}" data-new="{{ $new }}" data-exits="{{ $exits }}"
                    data-net="{{ $net }}" data-running="{{ $running }}" data-rate="{{ $rate }}"
                    class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">
                    <td data-label="Month" class="px-3 py-2.5 font-medium text-neutral-800">{{ $month }}</td>
                    <td data-label="New Members" class="px-3 py-2.5 text-right tabular-nums text-neutral-700">{{ $new }}</td>
                    <td data-label="Exits" class="px-3 py-2.5 text-right tabular-nums text-neutral-700">{{ $exits }}</td>
                    <td data-label="Net Change" class="px-3 py-2.5 text-right font-medium tabular-nums {{ $net >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $net >= 0 ? '+' : '' }}{{ $net }}
                    </td>
                    <td data-label="Running Total" class="px-3 py-2.5 text-right tabular-nums text-neutral-800">{{ number_format($running) }}</td>
                    <td data-label="Growth Rate" class="px-3 py-2.5 text-right tabular-nums text-neutral-600">{{ Format::percent($rate, 2) }}</td>
                </tr>
            @endforeach

            <x-slot:foot>
                <tr>
                    <td data-label="" class="px-3 py-2.5 text-[13px] font-semibold text-neutral-700">Total</td>
                    <td data-label="New Members" class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">{{ $netNew }}</td>
                    <td data-label="Exits" class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">{{ $netExits }}</td>
                    <td data-label="Net Change" class="px-3 py-2.5 text-right font-semibold tabular-nums text-success">+{{ $netNew - $netExits }}</td>
                    <td data-label="Running Total" class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">{{ number_format($running) }}</td>
                    <td data-label="" class="px-3 py-2.5"></td>
                </tr>
            </x-slot:foot>
        </x-data-table>
    </x-card>
</div>
