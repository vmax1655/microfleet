@php use App\Support\Format; @endphp

<div>
    <x-breadcrumb />

    <x-page-header
        title="Loan Accounts"
        subtitle="Every released loan, its repayment progress, and how far past due it is.">
        <x-slot:actions>
            <x-btn icon="download">Export</x-btn>
            <x-btn variant="primary" icon="plus" :href="route('loans.applications')">New Loan Application</x-btn>
        </x-slot:actions>
    </x-page-header>

    <section aria-label="Portfolio summary" class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Active Loan Accounts" :value="count($loans)" :delta="3.3" icon="file-text" />
        <x-stat-card label="Total Outstanding" :value="Format::pesoCompact($totals['outstanding'])" :delta="4.1" icon="wallet" />
        <x-stat-card label="Principal Released" :value="Format::pesoCompact($totals['principal'])" :delta="6.8" icon="banknote" />
        <x-stat-card label="Accounts Past Due" :value="$totals['overdue']" :delta="1.6" good-direction="down" icon="alert-triangle" />
    </section>

    <x-filter-bar search-label="Search loan accounts" search-placeholder="Loan ID, member name, or member ID…" date-range>
        <x-select label="Product" :options="collect($products)->pluck('name')->all()" placeholder="All products" width="w-48" />
        <x-select label="Status" :options="['Active', 'Overdue', 'Paid', 'Restructured', 'Written-off']" placeholder="All statuses" width="w-40" />
        <x-select label="Loan officer" :options="collect($officers)->pluck('name')->all()" placeholder="All officers" width="w-44" />
    </x-filter-bar>

    <x-card flush>
        @if(count($loans) === 0)
            <x-empty-state
                icon="file-text"
                heading="No loan accounts match these filters"
                help="Loan accounts are created automatically once an approved application is disbursed."
                action-label="Go to disbursements"
                :action-href="route('loans.disbursements')"
                action-icon="wallet" />
        @else
            <x-data-table sort-key="outstanding" sort-dir="desc" caption="All loan accounts">
                <x-slot:head>
                    <x-th sort="loan">Loan ID</x-th>
                    <x-th sort="member">Member</x-th>
                    <x-th sort="product">Product</x-th>
                    <x-th sort="principal" align="right">Principal</x-th>
                    <x-th sort="outstanding" align="right">Outstanding</x-th>
                    <x-th sort="progress">Progress</x-th>
                    <x-th sort="nextdue">Next Due</x-th>
                    <x-th sort="dpd" align="right">DPD</x-th>
                    <x-th sort="status">Status</x-th>
                    <x-th align="right" sr-only>Actions</x-th>
                </x-slot:head>

                @foreach($loans as $i => $loan)
                    <tr data-row data-loan="{{ $loan['id'] }}" data-member="{{ $loan['member'] }}"
                        data-product="{{ $loan['product'] }}" data-principal="{{ $loan['principal'] }}"
                        data-outstanding="{{ $loan['outstanding'] }}" data-progress="{{ $loan['progress'] }}"
                        data-nextdue="{{ $loan['next_due'] }}" data-dpd="{{ $loan['dpd'] }}" data-status="{{ $loan['status'] }}"
                        class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">

                        <td data-label="Loan ID" class="px-3 py-2.5">
                            <a href="{{ route('loans.accounts.show', $loan['id']) }}"
                               class="font-medium tabular-nums text-primary-700 hover:underline">{{ $loan['id'] }}</a>
                        </td>

                        <td data-label="Member" class="px-3 py-2.5">
                            <div class="flex items-center gap-2.5">
                                <x-avatar :name="$loan['member']" size="sm" />
                                <div class="min-w-0">
                                    <span class="block truncate font-medium text-neutral-800">{{ $loan['member'] }}</span>
                                    <span class="block truncate text-xs text-neutral-500">{{ $loan['center'] }}</span>
                                </div>
                            </div>
                        </td>

                        <td data-label="Product" class="px-3 py-2.5">
                            <span class="block text-neutral-700">{{ $loan['product'] }}</span>
                            <span class="block text-xs tabular-nums text-neutral-500">{{ $loan['rate'] }}% {{ $loan['method'] }} · {{ $loan['frequency'] }}</span>
                        </td>

                        <td data-label="Principal" class="px-3 py-2.5 text-right tabular-nums text-neutral-700">{{ Format::peso($loan['principal']) }}</td>
                        <td data-label="Outstanding" class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800">{{ Format::peso($loan['outstanding']) }}</td>

                        <td data-label="Progress" class="px-3 py-2.5">
                            <x-meter :value="$loan['progress']" tone="primary" size="sm" class="w-24" />
                        </td>

                        <td data-label="Next Due" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ Format::date($loan['next_due']) }}</td>

                        <td data-label="DPD" class="px-3 py-2.5 text-right tabular-nums {{ $loan['dpd'] > 0 ? 'font-medium text-danger' : 'text-neutral-500' }}">
                            {{ $loan['dpd'] > 0 ? $loan['dpd'] : '—' }}
                        </td>

                        <td data-label="Status" class="px-3 py-2.5"><x-status-badge :status="$loan['status']" /></td>

                        <td data-label="" class="px-3 py-2.5">
                            <x-row-actions :label="'Actions for '.$loan['id']">
                                <x-row-action icon="eye" :href="route('loans.accounts.show', $loan['id'])">Open account</x-row-action>
                                <x-row-action icon="banknote">Record payment</x-row-action>
                                <x-row-action icon="printer">Print statement</x-row-action>
                                <x-row-action icon="refresh-cw" :href="route('collections.penalties')">Request restructure</x-row-action>
                                <x-row-action icon="send">Send reminder</x-row-action>
                                <x-row-action icon="file-x" danger>Write off</x-row-action>
                            </x-row-actions>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>

            <x-pagination :from="1" :to="count($loans)" :total="1290" :current="1" :per-page="24" />
        @endif
    </x-card>
</div>
