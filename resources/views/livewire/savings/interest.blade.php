@php use App\Support\Format; @endphp

<div>
    <x-breadcrumb />

    <x-page-header
        title="Interest Posting"
        subtitle="Preview and post the quarterly interest credit across all savings accounts.">
        <x-slot:actions>
            <x-btn icon="download">Export preview</x-btn>
            <x-btn variant="primary" icon="percent" @click="$dispatch('open-modal', 'post-interest')">Post Interest to All</x-btn>
        </x-slot:actions>
    </x-page-header>

    <section aria-label="Interest run summary" class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Eligible Accounts" :value="number_format($totals['accounts'] * 72)" :delta="3.2" icon="wallet" />
        <x-stat-card label="Qualifying Balance" :value="Format::pesoCompact($totals['balance'] * 72)" :delta="2.8" icon="piggy-bank" />
        <x-stat-card label="Interest to Post" :value="Format::peso($totals['interest'] * 72)" :delta="4.6" icon="percent" />
        <x-stat-card label="Accounts Skipped" :value="number_format($totals['skipped'] * 72)" :delta="1.4" good-direction="down" icon="alert-circle"
                     hint="Dormant or closed" />
    </section>

    <x-filter-bar search-label="Search preview" search-placeholder="Account number or member name…" :export="false">
        <x-select label="Period" :options="['Q3 2026 (Jul–Sep)', 'Q2 2026 (Apr–Jun)', 'Q1 2026 (Jan–Mar)', 'Q4 2025 (Oct–Dec)']" width="w-52" />
        <x-select label="Product" :options="['Regular Savings', 'Kabataan Savings', 'Time Deposit (6mo)', 'Christmas Savings']" placeholder="All products" width="w-48" />
        <x-select label="Status" :options="['Pending', 'Skipped', 'Posted']" placeholder="All statuses" width="w-36" />
        <x-btn icon="refresh-cw">Recompute</x-btn>
    </x-filter-bar>

    <div class="mb-5 rounded-[12px] border border-[#B0CDFA] bg-[#EAF2FE] p-4">
        <div class="flex gap-2.5">
            <x-icon name="info" class="mt-0.5 shrink-0 text-info" />
            <div>
                <p class="text-[13px] font-medium text-info">This is a preview — nothing has been posted yet</p>
                <p class="mt-0.5 text-[13px] text-info/90">
                    Interest is computed as balance × annual rate × days ÷ 365, using the average daily balance for the
                    selected period. Review the figures, then post the batch.
                </p>
            </div>
        </div>
    </div>

    <x-card flush title="Interest Computation Preview" subtitle="Q3 2026 · 01 Jul 2026 – 30 Sep 2026 · 90 days">
        @if(count($preview) === 0)
            <x-empty-state
                icon="percent"
                heading="Nothing to compute for this period"
                help="Select a different quarter, or check that savings products have an interest rate configured."
                action-label="Manage products"
                :action-href="route('savings.accounts')"
                action-icon="piggy-bank" />
        @else
            <x-data-table sort-key="interest" sort-dir="desc" caption="Interest computation preview by account">
                <x-slot:head>
                    <x-th sort="account">Account</x-th>
                    <x-th sort="member">Member</x-th>
                    <x-th sort="product">Product</x-th>
                    <x-th sort="balance" align="right">Balance</x-th>
                    <x-th sort="rate" align="right">Rate</x-th>
                    <x-th sort="days" align="right">Days</x-th>
                    <x-th sort="interest" align="right">Interest Earned</x-th>
                    <x-th sort="newbalance" align="right">Balance After</x-th>
                    <x-th sort="status">Status</x-th>
                </x-slot:head>

                @foreach($preview as $i => $p)
                    <tr data-row data-account="{{ $p['account_no'] }}" data-member="{{ $p['member'] }}"
                        data-product="{{ $p['product'] }}" data-balance="{{ $p['balance'] }}"
                        data-rate="{{ $p['rate'] }}" data-days="{{ $p['days'] }}"
                        data-interest="{{ $p['interest'] }}" data-newbalance="{{ $p['balance'] + $p['interest'] }}"
                        data-status="{{ $p['status'] }}"
                        class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }} {{ $p['status'] === 'Skipped' ? 'opacity-60' : '' }}">

                        <td data-label="Account" class="px-3 py-2.5">
                            <a href="{{ route('savings.accounts.show', $p['account_no']) }}"
                               class="tabular-nums text-primary-700 hover:underline">{{ $p['account_no'] }}</a>
                        </td>
                        <td data-label="Member" class="px-3 py-2.5 text-neutral-700">{{ $p['member'] }}</td>
                        <td data-label="Product" class="px-3 py-2.5 text-neutral-600">{{ $p['product'] }}</td>
                        <td data-label="Balance" class="px-3 py-2.5 text-right tabular-nums text-neutral-700">{{ Format::peso($p['balance']) }}</td>
                        <td data-label="Rate" class="px-3 py-2.5 text-right tabular-nums text-neutral-600">{{ $p['rate'] }}%</td>
                        <td data-label="Days" class="px-3 py-2.5 text-right tabular-nums text-neutral-600">{{ $p['days'] }}</td>
                        <td data-label="Interest Earned" class="px-3 py-2.5 text-right font-medium tabular-nums {{ $p['status'] === 'Skipped' ? 'text-neutral-400' : 'text-success' }}">
                            {{ $p['status'] === 'Skipped' ? '—' : Format::peso($p['interest']) }}
                        </td>
                        <td data-label="Balance After" class="px-3 py-2.5 text-right tabular-nums text-neutral-800">
                            {{ Format::peso($p['status'] === 'Skipped' ? $p['balance'] : $p['balance'] + $p['interest']) }}
                        </td>
                        <td data-label="Status" class="px-3 py-2.5"><x-status-badge :status="$p['status']" /></td>
                    </tr>
                @endforeach

                <x-slot:foot>
                    <tr>
                        <td data-label="" class="px-3 py-2.5 text-[13px] font-semibold text-neutral-700" colspan="3">
                            Batch total ({{ $totals['accounts'] }} eligible accounts)
                        </td>
                        <td data-label="Balance" class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">{{ Format::peso($totals['balance']) }}</td>
                        <td data-label="" class="px-3 py-2.5" colspan="2"></td>
                        <td data-label="Interest Earned" class="px-3 py-2.5 text-right font-semibold tabular-nums text-success">{{ Format::peso($totals['interest']) }}</td>
                        <td data-label="Balance After" class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">
                            {{ Format::peso($totals['balance'] + $totals['interest']) }}
                        </td>
                        <td data-label="" class="px-3 py-2.5"></td>
                    </tr>
                </x-slot:foot>
            </x-data-table>

            <x-pagination :from="1" :to="count($preview)" :total="1284" :current="1" :per-page="18" />
        @endif
    </x-card>

    <x-modal name="post-interest" title="Post interest to all eligible accounts?" icon="percent" tone="warning"
             subtitle="Q3 2026 · this credits every eligible savings account in one batch.">
        <dl class="grid grid-cols-2 gap-4 rounded-[12px] border border-neutral-200 bg-neutral-50 p-4">
            <x-kpi label="Accounts to credit" :value="number_format($totals['accounts'] * 72)" />
            <x-kpi label="Batch total" :value="Format::peso($totals['interest'] * 72)" />
            <x-kpi label="Accounts skipped" :value="number_format($totals['skipped'] * 72)" hint="Dormant or closed" />
            <x-kpi label="Posting date" value="30 Sep 2026" />
        </dl>

        <div class="mt-4 flex items-start gap-2.5 rounded-[8px] border border-[#F5D28A] bg-[#FEF0D6] p-3.5">
            <x-icon name="alert-triangle" class="mt-0.5 shrink-0 text-warning" />
            <p class="text-[13px] text-warning">
                Interest posting cannot be reversed as a batch. Individual corrections must be made per account and
                will each appear in the audit log.
            </p>
        </div>

        <x-form-field class="mt-4" label="Approving officer" type="select" name="approver" required
                      :options="['Teresita G. Gonzales — Branch Manager', 'Editha C. Ramirez — Super Admin']" />

        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="primary" icon="check">Post Interest</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
