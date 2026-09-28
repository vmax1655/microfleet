@php
    use App\Support\Format;

    $overallEfficiency = $totals['target'] > 0 ? $totals['collected'] / $totals['target'] * 100 : 0;
@endphp

<div>
    <x-breadcrumb />

    <x-page-header
        title="Collection Performance"
        subtitle="How each loan officer is tracking against target, and what it costs the portfolio.">
        <x-slot:actions>
            <x-btn icon="printer">Print</x-btn>
            <x-btn variant="primary" icon="download">Export Report</x-btn>
        </x-slot:actions>
    </x-page-header>

    <section aria-label="Performance summary" class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Collection Target" :value="Format::pesoCompact($totals['target'])" :delta="4.2" icon="target" />
        <x-stat-card label="Amount Collected" :value="Format::pesoCompact($totals['collected'])" :delta="5.8" icon="banknote" />
        <x-stat-card label="Overall Efficiency" :value="Format::percent($overallEfficiency)" :delta="1.3" icon="trending-up" />
        <x-stat-card label="Accounts Assigned" :value="number_format($totals['accounts'])" :delta="2.6" icon="users" />
    </section>

    <x-filter-bar :show-search="false" date-range>
        <x-select label="Branch" :options="['All branches', 'Malolos Main Branch', 'Sta. Maria Branch', 'San Jose del Monte Branch', 'Baliuag Branch']" width="w-52" />
        <x-select label="Center" :options="collect($centers)->pluck('name')->all()" placeholder="All centers" width="w-52" />
        <x-select label="Period" :options="['This month', 'Last month', 'This quarter', 'Year to date']" width="w-40" />
    </x-filter-bar>

    <x-card class="mb-5" flush title="Loan Officer Leaderboard"
            subtitle="Ranked by collection efficiency for the selected period">
        @if(count($officers) === 0)
            <x-empty-state
                icon="award"
                heading="No officer data for this period"
                help="Assign accounts to loan officers and set monthly targets to start tracking performance."
                action-label="Manage users"
                :action-href="route('admin.users')"
                action-icon="users" />
        @else
            <x-data-table sort-key="efficiency" sort-dir="desc" caption="Loan officer collection performance">
                <x-slot:head>
                    <x-th sort="rank" align="right" width="56px">#</x-th>
                    <x-th sort="officer">Officer</x-th>
                    <x-th sort="branch">Branch</x-th>
                    <x-th sort="accounts" align="right">Assigned Accounts</x-th>
                    <x-th sort="target" align="right">Target</x-th>
                    <x-th sort="collected" align="right">Collected</x-th>
                    <x-th sort="efficiency">Efficiency</x-th>
                    <x-th sort="par" align="right">PAR Contribution</x-th>
                </x-slot:head>

                @foreach($officers as $i => $o)
                    <tr data-row data-rank="{{ $o['rank'] }}" data-officer="{{ $o['officer'] }}"
                        data-branch="{{ $o['branch'] }}" data-accounts="{{ $o['accounts'] }}"
                        data-target="{{ $o['target'] }}" data-collected="{{ $o['collected'] }}"
                        data-efficiency="{{ $o['efficiency'] }}" data-par="{{ $o['par_contribution'] }}"
                        class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">

                        <td data-label="Rank" class="px-3 py-2.5 text-right">
                            <span @class([
                                'inline-grid h-7 w-7 place-items-center rounded-full text-xs font-semibold tabular-nums',
                                'bg-accent-500 text-primary-950' => $o['rank'] === 1,
                                'bg-neutral-200 text-neutral-700' => $o['rank'] !== 1,
                            ])>{{ $o['rank'] }}</span>
                        </td>

                        <td data-label="Officer" class="px-3 py-2.5">
                            <div class="flex items-center gap-2.5">
                                <x-avatar :name="$o['officer']" size="sm" :tone="$o['rank'] === 1 ? 'accent' : 'primary'" />
                                <span class="truncate font-medium text-neutral-800">{{ $o['officer'] }}</span>
                            </div>
                        </td>

                        <td data-label="Branch" class="px-3 py-2.5 text-neutral-600">{{ $o['branch'] }}</td>
                        <td data-label="Assigned Accounts" class="px-3 py-2.5 text-right tabular-nums text-neutral-700">{{ number_format($o['accounts']) }}</td>
                        <td data-label="Target" class="px-3 py-2.5 text-right tabular-nums text-neutral-600">{{ Format::peso($o['target']) }}</td>
                        <td data-label="Collected" class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800">{{ Format::peso($o['collected']) }}</td>

                        <td data-label="Efficiency" class="px-3 py-2.5">
                            <x-meter :value="$o['efficiency']" :thresholds="[90, 96]" class="w-32" />
                        </td>

                        <td data-label="PAR Contribution" class="px-3 py-2.5 text-right font-medium tabular-nums {{ $o['par_contribution'] > 4 ? 'text-danger' : 'text-neutral-600' }}">
                            {{ Format::percent($o['par_contribution']) }}
                        </td>
                    </tr>
                @endforeach

                <x-slot:foot>
                    <tr>
                        <td data-label="" class="px-3 py-2.5"></td>
                        <td data-label="" class="px-3 py-2.5 text-[13px] font-semibold text-neutral-700">Branch total</td>
                        <td data-label="" class="px-3 py-2.5"></td>
                        <td data-label="Assigned Accounts" class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">{{ number_format($totals['accounts']) }}</td>
                        <td data-label="Target" class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">{{ Format::peso($totals['target']) }}</td>
                        <td data-label="Collected" class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">{{ Format::peso($totals['collected']) }}</td>
                        <td data-label="Efficiency" class="px-3 py-2.5 text-[13px] font-semibold tabular-nums text-neutral-900">{{ Format::percent($overallEfficiency) }}</td>
                        <td data-label="" class="px-3 py-2.5"></td>
                    </tr>
                </x-slot:foot>
            </x-data-table>
        @endif
    </x-card>

    <x-card title="Collection Efficiency Trend" subtitle="Amount collected against amount due, by branch">
        <x-slot:actions>
            <x-select label="Grouping" hide-label :options="['By branch', 'By officer', 'By center']" width="w-40" />
        </x-slot:actions>

        <x-charts.collection-efficiency-trend />
    </x-card>
</div>
