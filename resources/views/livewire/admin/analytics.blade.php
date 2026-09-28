@php
    use App\Support\Format;

    $totalPortfolio = array_sum(array_column($branches, 'portfolio'));
    $totalMembers = array_sum(array_column($branches, 'members'));
@endphp

<div>
    <x-breadcrumb />

    <x-page-header
        title="Analytics"
        subtitle="Portfolio, product mix, and branch performance across the cooperative.">
        <x-slot:actions>
            <x-btn icon="printer">Print</x-btn>
            <x-btn variant="primary" icon="download">Export Dashboard</x-btn>
        </x-slot:actions>
    </x-page-header>

    <x-filter-bar :show-search="false" :export="false" date-range>
        <x-select label="Branch" :options="collect($branches)->pluck('name')->all()" placeholder="All branches" width="w-52" />
        <x-select label="Product" :options="collect($products)->pluck('name')->all()" placeholder="All products" width="w-48" />
        <x-select label="Comparison" :options="['vs previous period', 'vs same period last year', 'No comparison']" width="w-52" />
    </x-filter-bar>

    <section aria-label="Analytics summary" class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Gross Portfolio" :value="Format::pesoCompact($totalPortfolio)" :delta="4.8" icon="wallet" />
        <x-stat-card label="Total Members" :value="number_format($totalMembers)" :delta="3.6" icon="users" />
        <x-stat-card label="Repayment Rate" value="96.9%" :delta="0.5" icon="target" />
        <x-stat-card label="Blended PAR > 30" value="4.2%" :delta="0.3" good-direction="down" icon="alert-triangle" />
    </section>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

        <x-card class="lg:col-span-2" title="Portfolio Growth" subtitle="Loan portfolio and savings balance, last 12 months">
            <x-charts.portfolio-growth />
        </x-card>

        <x-card title="Loan Product Mix" subtitle="Share of outstanding portfolio">
            <x-charts.product-mix height="h-64" />

            <dl class="mt-4 space-y-2 border-t border-neutral-200 pt-4">
                @foreach($products as $i => $p)
                    @continue($p['portfolio'] === 0)
                    <div class="flex items-center justify-between gap-3">
                        <dt class="flex min-w-0 items-center gap-2 text-[13px] text-neutral-600">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-[3px]" style="background-color: {{ Format::CHART_COLORS[$i % count(Format::CHART_COLORS)] }}" aria-hidden="true"></span>
                            <span class="truncate">{{ $p['name'] }}</span>
                        </dt>
                        <dd class="shrink-0 text-[13px] font-medium tabular-nums text-neutral-800">{{ number_format($p['active_loans']) }} loans</dd>
                    </div>
                @endforeach
            </dl>
        </x-card>

        <x-card class="lg:col-span-2" title="Disbursement by Branch" subtitle="Amount released this month">
            <x-charts.disbursement-by-branch />
        </x-card>

        <x-card title="Repayment Rate Trend" subtitle="Cooperative-wide, last 12 months">
            <x-charts.repayment-rate-trend height="h-64" />
        </x-card>
    </div>

    <x-card class="mt-5" flush title="Branch Comparison" subtitle="Side-by-side performance across every branch">
        <x-data-table sort-key="portfolio" sort-dir="desc" caption="Branch performance comparison">
            <x-slot:head>
                <x-th sort="branch">Branch</x-th>
                <x-th sort="manager">Manager</x-th>
                <x-th sort="opened">Opened</x-th>
                <x-th sort="members" align="right">Members</x-th>
                <x-th sort="portfolio" align="right">Portfolio</x-th>
                <x-th sort="avgloan" align="right">Avg. Loan Size</x-th>
                <x-th sort="par" align="right">PAR > 30</x-th>
                <x-th sort="health">Portfolio Health</x-th>
            </x-slot:head>

            @foreach($branches as $i => $b)
                @php $avgLoan = $b['portfolio'] / max(1, $b['members']); @endphp
                <tr data-row data-branch="{{ $b['name'] }}" data-manager="{{ $b['manager'] }}"
                    data-opened="{{ $b['opened'] }}" data-members="{{ $b['members'] }}"
                    data-portfolio="{{ $b['portfolio'] }}" data-avgloan="{{ $avgLoan }}"
                    data-par="{{ $b['par30'] }}" data-health="{{ 100 - $b['par30'] }}"
                    class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">

                    <td data-label="Branch" class="px-3 py-2.5">
                        <span class="block font-medium text-neutral-800">{{ $b['name'] }}</span>
                        <span class="block text-xs text-neutral-500">{{ $b['city'] }}</span>
                    </td>
                    <td data-label="Manager" class="px-3 py-2.5 text-neutral-600">{{ $b['manager'] }}</td>
                    <td data-label="Opened" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ Format::date($b['opened']) }}</td>
                    <td data-label="Members" class="px-3 py-2.5 text-right tabular-nums text-neutral-700">{{ number_format($b['members']) }}</td>
                    <td data-label="Portfolio" class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800">{{ Format::peso($b['portfolio']) }}</td>
                    <td data-label="Avg. Loan Size" class="px-3 py-2.5 text-right tabular-nums text-neutral-600">{{ Format::peso($avgLoan) }}</td>
                    <td data-label="PAR > 30" class="px-3 py-2.5 text-right font-semibold tabular-nums {{ $b['par30'] > 5 ? 'text-danger' : 'text-neutral-700' }}">
                        {{ Format::percent($b['par30']) }}
                    </td>
                    <td data-label="Portfolio Health" class="px-3 py-2.5">
                        <x-meter :value="100 - $b['par30']" :thresholds="[94, 96]" class="w-32" />
                    </td>
                </tr>
            @endforeach

            <x-slot:foot>
                <tr>
                    <td data-label="" class="px-3 py-2.5 text-[13px] font-semibold text-neutral-700" colspan="3">All branches</td>
                    <td data-label="Members" class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">{{ number_format($totalMembers) }}</td>
                    <td data-label="Portfolio" class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">{{ Format::peso($totalPortfolio) }}</td>
                    <td data-label="Avg. Loan Size" class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">{{ Format::peso($totalPortfolio / max(1, $totalMembers)) }}</td>
                    <td data-label="PAR > 30" class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">4.2%</td>
                    <td data-label="" class="px-3 py-2.5"></td>
                </tr>
            </x-slot:foot>
        </x-data-table>
    </x-card>
</div>
