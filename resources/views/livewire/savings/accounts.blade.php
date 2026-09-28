@php use App\Support\Format; @endphp

<div>
    <x-breadcrumb />

    <x-page-header
        title="Savings Accounts"
        subtitle="Member deposit accounts across all savings products.">
        <x-slot:actions>
            <x-btn icon="download">Export</x-btn>
            <x-btn variant="primary" icon="plus">Open Account</x-btn>
        </x-slot:actions>
    </x-page-header>

    <section aria-label="Savings summary" class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Total Savings Balance" :value="Format::pesoCompact($totals['balance'] * 42)" :delta="2.9" icon="piggy-bank" />
        <x-stat-card label="Active Accounts" :value="number_format($totals['active'] * 48)" :delta="3.4" icon="wallet" />
        <x-stat-card label="Dormant Accounts" :value="number_format($totals['dormant'] * 22)" :delta="1.8" good-direction="down" icon="clock" />
        <x-stat-card label="Avg. Balance" :value="Format::peso($totals['balance'] / max(1, count($accounts)))" :delta="1.1" icon="banknote" />
    </section>

    <x-filter-bar search-label="Search accounts" search-placeholder="Account number, member name, or member ID…">
        <x-select label="Product" :options="['Regular Savings', 'Kabataan Savings', 'Time Deposit (6mo)', 'Christmas Savings']" placeholder="All products" width="w-48" />
        <x-select label="Status" :options="['Active', 'Dormant', 'Closed']" placeholder="All statuses" width="w-36" />
        <x-select label="Balance" :options="['Below ₱1,000', '₱1,000 – ₱10,000', '₱10,001 – ₱50,000', 'Above ₱50,000']" placeholder="Any balance" width="w-44" />
    </x-filter-bar>

    <x-card flush>
        @if(count($accounts) === 0)
            <x-empty-state
                icon="piggy-bank"
                heading="No savings accounts match these filters"
                help="Every member is issued a regular savings account on enrolment. Widen the filters or open a new account."
                action-label="Open Account" />
        @else
            <x-data-table sort-key="balance" sort-dir="desc" caption="Member savings accounts">
                <x-slot:head>
                    <x-th sort="account">Account No.</x-th>
                    <x-th sort="member">Member</x-th>
                    <x-th sort="product">Product</x-th>
                    <x-th sort="rate" align="right">Rate</x-th>
                    <x-th sort="balance" align="right">Balance</x-th>
                    <x-th sort="lasttxn">Last Transaction</x-th>
                    <x-th sort="status">Status</x-th>
                    <x-th align="right" sr-only>Actions</x-th>
                </x-slot:head>

                @foreach($accounts as $i => $a)
                    <tr data-row data-account="{{ $a['account_no'] }}" data-member="{{ $a['member'] }}"
                        data-product="{{ $a['product'] }}" data-rate="{{ $a['rate'] }}"
                        data-balance="{{ $a['balance'] }}" data-lasttxn="{{ $a['last_txn'] }}"
                        data-status="{{ $a['status'] }}"
                        class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">

                        <td data-label="Account No." class="px-3 py-2.5">
                            <a href="{{ route('savings.accounts.show', $a['account_no']) }}"
                               class="font-medium tabular-nums text-primary-700 hover:underline">{{ $a['account_no'] }}</a>
                        </td>

                        <td data-label="Member" class="px-3 py-2.5">
                            <div class="flex items-center gap-2.5">
                                <x-avatar :name="$a['member']" size="sm" />
                                <div class="min-w-0">
                                    <span class="block truncate font-medium text-neutral-800">{{ $a['member'] }}</span>
                                    <span class="block truncate text-xs text-neutral-500">{{ $a['center'] }}</span>
                                </div>
                            </div>
                        </td>

                        <td data-label="Product" class="px-3 py-2.5 text-neutral-700">{{ $a['product'] }}</td>
                        <td data-label="Rate" class="px-3 py-2.5 text-right tabular-nums text-neutral-600">{{ $a['rate'] }}%</td>
                        <td data-label="Balance" class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800">{{ Format::peso($a['balance']) }}</td>
                        <td data-label="Last Transaction" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ Format::date($a['last_txn']) }}</td>
                        <td data-label="Status" class="px-3 py-2.5"><x-status-badge :status="$a['status']" /></td>

                        <td data-label="" class="px-3 py-2.5">
                            <x-row-actions :label="'Actions for '.$a['account_no']">
                                <x-row-action icon="eye" :href="route('savings.accounts.show', $a['account_no'])">View ledger</x-row-action>
                                <x-row-action icon="arrow-down" :href="route('savings.transactions')">Post deposit</x-row-action>
                                <x-row-action icon="arrow-up" :href="route('savings.transactions')">Post withdrawal</x-row-action>
                                <x-row-action icon="printer">Print passbook</x-row-action>
                                <x-row-action icon="x" danger>Close account</x-row-action>
                            </x-row-actions>
                        </td>
                    </tr>
                @endforeach

                <x-slot:foot>
                    <tr>
                        <td data-label="" class="px-3 py-2.5 text-[13px] font-semibold text-neutral-700" colspan="4">Total for this page</td>
                        <td data-label="Balance" class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">{{ Format::peso($totals['balance']) }}</td>
                        <td data-label="" class="px-3 py-2.5" colspan="3"></td>
                    </tr>
                </x-slot:foot>
            </x-data-table>

            <x-pagination :from="1" :to="count($accounts)" :total="1284" :current="1" :per-page="26" />
        @endif
    </x-card>
</div>
