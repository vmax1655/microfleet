@php use App\Support\Format; @endphp

<div>
    <x-breadcrumb />

    <x-page-header
        title="Repayment Ledger"
        subtitle="Every receipt issued against a loan account, searchable by OR number.">
        <x-slot:actions>
            <x-btn icon="printer">Print</x-btn>
            <x-btn variant="primary" icon="plus" :href="route('collections.sheet')">Record Collection</x-btn>
        </x-slot:actions>
    </x-page-header>

    <section aria-label="Ledger summary" class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Receipts Issued" :value="number_format($totals['receipts'])" :delta="5.2" icon="receipt" />
        <x-stat-card label="Total Collected" :value="Format::pesoCompact($totals['amount'])" :delta="6.7" icon="banknote" />
        <x-stat-card label="Posted to Ledger" :value="Format::pesoCompact($totals['posted'])" :delta="6.1" icon="check-circle" />
        <x-stat-card label="Collected via GCash" :value="Format::pesoCompact($totals['gcash'])" :delta="18.4" icon="phone"
                     hint="Share of total: 24%" />
    </section>

    <x-filter-bar search-label="Search receipts" search-placeholder="OR number, member name, or loan ID…" date-range>
        <x-select label="Center" :options="collect($centers)->pluck('name')->all()" placeholder="All centers" width="w-52" />
        <x-select label="Method" :options="['Cash', 'GCash', 'Bank Transfer']" placeholder="Any method" width="w-40" />
        <x-select label="Status" :options="['Posted', 'Pending', 'Failed']" placeholder="All statuses" width="w-36" />
    </x-filter-bar>

    <x-card flush>
        @if(count($rows) === 0)
            <x-empty-state
                icon="receipt"
                heading="No receipts in this period"
                help="Adjust the date range, or post today's collections from the daily collection sheet."
                action-label="Open collection sheet"
                :action-href="route('collections.sheet')"
                action-icon="clipboard-list" />
        @else
            <x-data-table sort-key="date" sort-dir="desc" caption="Repayment ledger transactions">
                <x-slot:head>
                    <x-th sort="or">OR Number</x-th>
                    <x-th sort="date">Date</x-th>
                    <x-th sort="member">Member</x-th>
                    <x-th sort="loan">Loan ID</x-th>
                    <x-th sort="amount" align="right">Amount</x-th>
                    <x-th sort="method">Method</x-th>
                    <x-th sort="received">Received By</x-th>
                    <x-th sort="status">Status</x-th>
                    <x-th align="right" sr-only>Actions</x-th>
                </x-slot:head>

                @foreach($rows as $i => $r)
                    <tr data-row data-or="{{ $r['or_no'] }}" data-date="{{ $r['date'].' '.$r['time'] }}"
                        data-member="{{ $r['member'] }}" data-loan="{{ $r['loan_id'] }}"
                        data-amount="{{ $r['amount'] }}" data-method="{{ $r['method'] }}"
                        data-received="{{ $r['received_by'] }}" data-status="{{ $r['status'] }}"
                        class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">

                        <td data-label="OR Number" class="px-3 py-2.5 font-medium tabular-nums text-neutral-800">{{ $r['or_no'] }}</td>

                        <td data-label="Date" class="px-3 py-2.5">
                            <span class="block tabular-nums text-neutral-700">{{ Format::date($r['date']) }}</span>
                            <span class="block text-xs tabular-nums text-neutral-500">{{ $r['time'] }}</span>
                        </td>

                        <td data-label="Member" class="px-3 py-2.5">
                            <div class="flex items-center gap-2.5">
                                <x-avatar :name="$r['member']" size="sm" />
                                <div class="min-w-0">
                                    <a href="{{ route('membership.profile', $r['member_id']) }}"
                                       class="block truncate font-medium text-neutral-800 hover:text-primary-700 hover:underline">{{ $r['member'] }}</a>
                                    <span class="block truncate text-xs text-neutral-500">{{ $r['center'] }}</span>
                                </div>
                            </div>
                        </td>

                        <td data-label="Loan ID" class="px-3 py-2.5">
                            <a href="{{ route('loans.accounts.show', $r['loan_id']) }}"
                               class="tabular-nums text-primary-700 hover:underline">{{ $r['loan_id'] }}</a>
                        </td>

                        <td data-label="Amount" class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800">{{ Format::peso($r['amount']) }}</td>

                        <td data-label="Method" class="px-3 py-2.5">
                            <span class="inline-flex items-center gap-1.5 text-neutral-700">
                                <x-icon :name="$r['method'] === 'Cash' ? 'banknote' : ($r['method'] === 'GCash' ? 'phone' : 'building-2')"
                                        class="h-4 w-4 text-neutral-400" />
                                {{ $r['method'] }}
                            </span>
                        </td>

                        <td data-label="Received By" class="px-3 py-2.5 text-neutral-600">{{ $r['received_by'] }}</td>
                        <td data-label="Status" class="px-3 py-2.5"><x-status-badge :status="$r['status']" /></td>

                        <td data-label="" class="px-3 py-2.5">
                            <x-row-actions :label="'Actions for receipt '.$r['or_no']">
                                <x-row-action icon="printer">Reprint receipt</x-row-action>
                                <x-row-action icon="eye" :href="route('loans.accounts.show', $r['loan_id'])">Open loan</x-row-action>
                                <x-row-action icon="send">Send SMS copy</x-row-action>
                                <x-row-action icon="x" danger @click="$dispatch('open-modal', 'void-receipt')">Void receipt</x-row-action>
                            </x-row-actions>
                        </td>
                    </tr>
                @endforeach

                <x-slot:foot>
                    <tr>
                        <td data-label="" class="px-3 py-2.5 text-[13px] font-semibold text-neutral-700" colspan="4">
                            Total for this page
                        </td>
                        <td data-label="Amount" class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">
                            {{ Format::peso($totals['amount']) }}
                        </td>
                        <td data-label="" class="px-3 py-2.5" colspan="4"></td>
                    </tr>
                </x-slot:foot>
            </x-data-table>

            <x-pagination :from="1" :to="count($rows)" :total="3184" :current="1" :per-page="24" />
        @endif
    </x-card>

    <x-modal name="void-receipt" title="Void this receipt?" tone="danger" icon="alert-triangle"
             subtitle="The payment is reversed on the loan ledger and the OR number is retired.">
        <p class="text-sm text-neutral-600">
            Voided receipts remain visible in the audit log with your name, the timestamp, and the reason below.
            A replacement receipt must be issued separately.
        </p>
        <x-form-field class="mt-4" label="Reason for voiding" type="textarea" name="void_reason" rows="3" required
                      placeholder="e.g. Amount was keyed as ₱1,240.00 instead of ₱1,420.00" />

        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="danger" icon="x">Void Receipt</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
