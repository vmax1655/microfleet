@php use App\Support\Format; @endphp

<div>
    <x-breadcrumb />

    <x-page-header
        title="Share Capital"
        subtitle="Member equity in the cooperative, held as ₱100.00 par-value shares.">
        <x-slot:actions>
            <x-btn icon="download">Export</x-btn>
            <x-btn variant="primary" icon="plus" @click="$dispatch('open-modal', 'post-contribution')">Post Contribution</x-btn>
        </x-slot:actions>
    </x-page-header>

    <section aria-label="Share capital summary" class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Total Share Capital" :value="Format::pesoCompact($totals['capital'] * 62)" :delta="4.4" icon="coins" />
        <x-stat-card label="Shares Outstanding" :value="number_format($totals['shares'] * 62)" :delta="4.4" icon="pie-chart" />
        <x-stat-card label="Contributing Members" :value="number_format(count($holdings) * 62)" :delta="3.1" icon="users" />
        <x-stat-card label="Avg. Holding" :value="Format::peso($totals['capital'] / max(1, count($holdings)))" :delta="1.3" icon="banknote" />
    </section>

    <x-filter-bar search-label="Search holdings" search-placeholder="Member name or member ID…">
        <x-select label="Center" :options="collect($centers)->pluck('name')->all()" placeholder="All centers" width="w-52" />
        <x-select label="Status" :options="['Active', 'Inactive']" placeholder="All statuses" width="w-36" />
        <x-select label="Holding size" :options="['Below 50 shares', '50 – 100 shares', 'Above 100 shares']" placeholder="Any size" width="w-44" />
    </x-filter-bar>

    <x-card flush>
        @if(count($holdings) === 0)
            <x-empty-state
                icon="coins"
                heading="No share holdings recorded"
                help="Every member subscribes to a minimum of 5 shares on enrolment. Post a contribution to get started."
                action-label="Post Contribution" />
        @else
            <x-data-table sort-key="capital" sort-dir="desc" caption="Member share capital holdings">
                <x-slot:head>
                    <x-th sort="member">Member</x-th>
                    <x-th sort="center">Center</x-th>
                    <x-th sort="shares" align="right">Shares</x-th>
                    <x-th sort="par" align="right">Par Value</x-th>
                    <x-th sort="capital" align="right">Total Capital</x-th>
                    <x-th sort="lastdate">Last Contribution</x-th>
                    <x-th sort="lastamount" align="right">Last Amount</x-th>
                    <x-th sort="status">Status</x-th>
                    <x-th align="right" sr-only>Actions</x-th>
                </x-slot:head>

                @foreach($holdings as $i => $h)
                    <tr data-row data-member="{{ $h['member'] }}" data-center="{{ $h['center'] }}"
                        data-shares="{{ $h['shares'] }}" data-par="{{ $h['par_value'] }}"
                        data-capital="{{ $h['total_capital'] }}" data-lastdate="{{ $h['last_contribution'] }}"
                        data-lastamount="{{ $h['last_amount'] }}" data-status="{{ $h['status'] }}"
                        class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">

                        <td data-label="Member" class="px-3 py-2.5">
                            <div class="flex items-center gap-2.5">
                                <x-avatar :name="$h['member']" size="sm" />
                                <div class="min-w-0">
                                    <a href="{{ route('membership.profile', $h['member_id']) }}"
                                       class="block truncate font-medium text-neutral-800 hover:text-primary-700 hover:underline">{{ $h['member'] }}</a>
                                    <span class="block text-xs tabular-nums text-neutral-500">{{ $h['member_id'] }}</span>
                                </div>
                            </div>
                        </td>

                        <td data-label="Center" class="px-3 py-2.5 text-neutral-600">{{ $h['center'] }}</td>
                        <td data-label="Shares" class="px-3 py-2.5 text-right tabular-nums text-neutral-700">{{ number_format($h['shares']) }}</td>
                        <td data-label="Par Value" class="px-3 py-2.5 text-right tabular-nums text-neutral-600">{{ Format::peso($h['par_value']) }}</td>
                        <td data-label="Total Capital" class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800">{{ Format::peso($h['total_capital']) }}</td>
                        <td data-label="Last Contribution" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ Format::date($h['last_contribution']) }}</td>
                        <td data-label="Last Amount" class="px-3 py-2.5 text-right tabular-nums text-neutral-600">{{ Format::peso($h['last_amount']) }}</td>
                        <td data-label="Status" class="px-3 py-2.5"><x-status-badge :status="$h['status']" /></td>

                        <td data-label="" class="px-3 py-2.5 text-right">
                            <x-btn size="sm" icon="plus" @click="$dispatch('open-modal', 'post-contribution')">Contribute</x-btn>
                        </td>
                    </tr>
                @endforeach

                <x-slot:foot>
                    <tr>
                        <td data-label="" class="px-3 py-2.5 text-[13px] font-semibold text-neutral-700" colspan="2">Total for this page</td>
                        <td data-label="Shares" class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">{{ number_format($totals['shares']) }}</td>
                        <td data-label="" class="px-3 py-2.5"></td>
                        <td data-label="Total Capital" class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">{{ Format::peso($totals['capital']) }}</td>
                        <td data-label="" class="px-3 py-2.5" colspan="4"></td>
                    </tr>
                </x-slot:foot>
            </x-data-table>

            <x-pagination :from="1" :to="count($holdings)" :total="1248" :current="1" :per-page="20" />
        @endif
    </x-card>

    {{-- ================================================================ contribution modal --}}
    <x-modal name="post-contribution" title="Post share capital contribution" icon="coins"
             subtitle="Shares are issued in whole units at ₱100.00 par value.">
        <div x-data="{ shares: 5, par: 100 }" class="space-y-4">
            <x-form-field label="Member" type="select" name="member" required
                          :options="collect($holdings)->pluck('member')->all()" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form-field label="Number of shares" type="number" name="shares" required tabular
                              x-model.number="shares" min="1" step="1" />
                <x-form-field label="Par value per share" name="par_value" prefix="₱" value="100.00" tabular disabled
                              help="Set in Branches & Settings." />
                <x-form-field label="Contribution date" type="date" name="date" value="2026-09-09" required tabular />
                <x-form-field label="OR number" name="or_no" value="OR-2026-0084915" required tabular />
            </div>

            <x-form-field label="Payment method" type="select" name="method" required
                          :options="['Cash', 'GCash', 'Bank Transfer', 'Deduction from savings']" />

            <div class="flex items-center justify-between rounded-[8px] border border-primary-200 bg-primary-50 p-3.5">
                <span class="text-[13px] font-medium text-primary-800">Total contribution</span>
                <span class="text-[17px] font-semibold tabular-nums text-primary-800"
                      x-text="window.LedgerFormat.peso(shares * par)">₱500.00</span>
            </div>
        </div>

        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="primary" icon="check">Post Contribution</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
