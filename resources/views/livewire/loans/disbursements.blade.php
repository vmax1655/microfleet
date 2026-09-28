@php
    use App\Support\Format;

    $releasable = collect($queue)->where('status', 'For Release')->values();
    $ids = $releasable->pluck('id')->all();
    $amounts = $releasable->mapWithKeys(fn ($r) => [$r['id'] => $r['net_proceeds']])->all();
@endphp

<div x-data="bulkSelect(@js($ids))">
    <x-breadcrumb />

    <x-page-header
        title="Disbursements"
        subtitle="Approved loans waiting to be released to members.">
        <x-slot:actions>
            <x-btn icon="printer">Print vouchers</x-btn>
            <x-btn icon="download">Export</x-btn>
            <x-btn variant="primary" icon="banknote"
                   ::disabled="count() === 0"
                   @click="$dispatch('open-modal', 'release-funds')">
                Release Funds<span x-show="count() > 0" x-cloak>&nbsp;(<span x-text="count()"></span>)</span>
            </x-btn>
        </x-slot:actions>
    </x-page-header>

    <section aria-label="Disbursement summary" class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Awaiting Release" :value="$totals['forRelease']" :delta="8.3" icon="clipboard-list" />
        <x-stat-card label="Approved Value" :value="Format::pesoCompact($totals['amount'])" :delta="6.4" icon="wallet" />
        <x-stat-card label="Net Proceeds" :value="Format::pesoCompact($totals['net'])" :delta="6.4" icon="banknote"
                     hint="After processing fees" />
        <x-stat-card label="Released This Month" :value="Format::pesoCompact(5420000)" :delta="4.9" icon="check-circle" />
    </section>

    <x-filter-bar search-label="Search queue" search-placeholder="Loan ID, member name, or disbursement ID…" date-range>
        <x-select label="Product" :options="collect($products)->pluck('name')->all()" placeholder="All products" width="w-48" />
        <x-select label="Release method" :options="['Cash', 'GCash', 'Bank Transfer']" placeholder="Any method" width="w-40" />
        <x-select label="Status" :options="['For Release', 'On Hold', 'Released']" placeholder="All statuses" width="w-40" />
    </x-filter-bar>

    <x-card flush>
        {{-- Batch bar --}}
        <div x-show="count() > 0" x-cloak
             class="flex flex-wrap items-center gap-3 border-b border-primary-200 bg-primary-50 px-4 py-2.5">
            <p class="text-[13px] font-medium text-primary-800">
                <span x-text="count()">0</span> loans selected for release
            </p>
            <p class="text-[13px] tabular-nums text-primary-800">
                Batch total:
                <span class="font-semibold"
                      x-text="window.LedgerFormat.peso(selected.reduce((s, id) => s + (@js($amounts))[id], 0))">₱0.00</span>
            </p>
            <button type="button" @click="selected = []" class="ml-auto text-[13px] font-medium text-primary-700 hover:underline">
                Clear selection
            </button>
        </div>

        @if(count($queue) === 0)
            <x-empty-state
                icon="wallet"
                heading="Nothing waiting for release"
                help="Approved applications arrive here automatically once a credit decision is recorded."
                action-label="Review applications"
                :action-href="route('loans.applications')"
                action-icon="file-text" />
        @else
            <x-data-table sort-key="amount" sort-dir="desc" caption="Approved loans awaiting disbursement">
                <x-slot:head>
                    <th scope="col" class="w-10 px-3 py-2.5">
                        <input type="checkbox"
                               @change="toggleAll($event)"
                               :checked="allChecked"
                               :indeterminate="someChecked"
                               aria-label="Select all releasable loans">
                    </th>
                    <x-th sort="member">Member</x-th>
                    <x-th sort="loan">Loan ID</x-th>
                    <x-th sort="amount" align="right">Amount</x-th>
                    <x-th sort="net" align="right">Net Proceeds</x-th>
                    <x-th sort="product">Product</x-th>
                    <x-th sort="approvedby">Approved By</x-th>
                    <x-th sort="method">Release Method</x-th>
                    <x-th sort="status">Status</x-th>
                    <x-th align="right" sr-only>Actions</x-th>
                </x-slot:head>

                @foreach($queue as $i => $row)
                    @php $selectable = $row['status'] === 'For Release'; @endphp
                    <tr data-row data-member="{{ $row['member'] }}" data-loan="{{ $row['loan_id'] }}"
                        data-amount="{{ $row['amount'] }}" data-net="{{ $row['net_proceeds'] }}"
                        data-product="{{ $row['product'] }}" data-approvedby="{{ $row['approved_by'] }}"
                        data-method="{{ $row['method'] }}" data-status="{{ $row['status'] }}"
                        class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">

                        <td data-label="" class="px-3 py-2.5">
                            <input type="checkbox"
                                   value="{{ $row['id'] }}"
                                   x-model="selected"
                                   @disabled(! $selectable)
                                   aria-label="Select {{ $row['loan_id'] }} for release">
                        </td>

                        <td data-label="Member" class="px-3 py-2.5">
                            <div class="flex items-center gap-2.5">
                                <x-avatar :name="$row['member']" size="sm" />
                                <div class="min-w-0">
                                    <span class="block truncate font-medium text-neutral-800">{{ $row['member'] }}</span>
                                    <span class="block truncate text-xs text-neutral-500">{{ $row['center'] }}</span>
                                </div>
                            </div>
                        </td>

                        <td data-label="Loan ID" class="px-3 py-2.5 tabular-nums text-neutral-700">{{ $row['loan_id'] }}</td>
                        <td data-label="Amount" class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800">{{ Format::peso($row['amount']) }}</td>
                        <td data-label="Net Proceeds" class="px-3 py-2.5 text-right tabular-nums text-neutral-600">{{ Format::peso($row['net_proceeds']) }}</td>
                        <td data-label="Product" class="px-3 py-2.5 text-neutral-700">{{ $row['product'] }}</td>

                        <td data-label="Approved By" class="px-3 py-2.5">
                            <span class="block text-neutral-700">{{ $row['approved_by'] }}</span>
                            <span class="block text-xs tabular-nums text-neutral-500">{{ Format::date($row['approved_on']) }}</span>
                        </td>

                        <td data-label="Release Method" class="px-3 py-2.5">
                            <span class="inline-flex items-center gap-1.5 text-neutral-700">
                                <x-icon :name="$row['method'] === 'Cash' ? 'banknote' : ($row['method'] === 'GCash' ? 'phone' : 'building-2')"
                                        class="h-4 w-4 text-neutral-400" />
                                {{ $row['method'] }}
                            </span>
                            @if($row['gcash'])
                                <span class="block text-xs tabular-nums text-neutral-500">{{ $row['gcash'] }}</span>
                            @endif
                        </td>

                        <td data-label="Status" class="px-3 py-2.5"><x-status-badge :status="$row['status']" /></td>

                        <td data-label="" class="px-3 py-2.5">
                            <x-row-actions :label="'Actions for '.$row['loan_id']">
                                <x-row-action icon="eye">View application</x-row-action>
                                <x-row-action icon="printer">Print voucher</x-row-action>
                                <x-row-action icon="pencil">Change release method</x-row-action>
                                <x-row-action icon="clock">Put on hold</x-row-action>
                                <x-row-action icon="x" danger>Cancel release</x-row-action>
                            </x-row-actions>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>

            <x-pagination :from="1" :to="count($queue)" :total="count($queue)" :current="1" />
        @endif
    </x-card>

    {{-- ================================================================ confirmation --}}
    <x-modal name="release-funds" title="Release funds for the selected loans?" icon="banknote" tone="warning"
             subtitle="This posts the disbursement to the teller blotter and creates the loan accounts.">
        <div class="rounded-[12px] border border-neutral-200 bg-neutral-50 p-4">
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-neutral-500">Loans in batch</dt>
                    <dd class="mt-1 text-lg font-semibold tabular-nums text-neutral-900" x-text="count()">0</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-neutral-500">Batch total (net)</dt>
                    <dd class="mt-1 text-lg font-semibold tabular-nums text-neutral-900"
                        x-text="window.LedgerFormat.peso(selected.reduce((s, id) => s + (@js($amounts))[id], 0))">₱0.00</dd>
                </div>
            </dl>
        </div>

        <ul class="mt-4 max-h-48 space-y-1.5 overflow-y-auto">
            <template x-for="id in selected" :key="id">
                <li class="flex items-center justify-between gap-3 rounded-[8px] border border-neutral-200 px-3 py-2 text-[13px]">
                    <span class="tabular-nums text-neutral-700" x-text="id"></span>
                    <span class="font-medium tabular-nums text-neutral-800"
                          x-text="window.LedgerFormat.peso((@js($amounts))[id])"></span>
                </li>
            </template>
        </ul>

        <x-form-field class="mt-4" label="Release date" type="date" name="release_date" value="2026-09-09" required tabular />

        <div class="mt-4 flex items-start gap-2.5 rounded-[8px] border border-[#F5D28A] bg-[#FEF0D6] p-3.5">
            <x-icon name="alert-triangle" class="mt-0.5 shrink-0 text-warning" />
            <p class="text-[13px] text-warning">
                Releasing funds cannot be undone. Cash releases must be counted against the teller blotter before the
                day is closed.
            </p>
        </div>

        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="primary" icon="check">Confirm Release</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
