@php
    use App\Support\Format;

    // Aging cards use the PAR ramp as a left accent, skipping "Current" and
    // adding a written-off tile at the end.
    $ramp = Format::PAR_RAMP;

    $cards = [
        ['label' => '1–30 days', 'amount' => $buckets[1]['amount'], 'accounts' => $buckets[1]['accounts'], 'accent' => $ramp[1], 'delta' => 1.2],
        ['label' => '31–60 days', 'amount' => $buckets[2]['amount'], 'accounts' => $buckets[2]['accounts'], 'accent' => $ramp[2], 'delta' => 0.7],
        ['label' => '61–90 days', 'amount' => $buckets[3]['amount'], 'accounts' => $buckets[3]['accounts'], 'accent' => $ramp[3], 'delta' => -0.4],
        ['label' => '90+ days', 'amount' => $buckets[4]['amount'], 'accounts' => $buckets[4]['accounts'], 'accent' => $ramp[4], 'delta' => 2.1],
        ['label' => 'Written-off', 'amount' => 1_180_000, 'accounts' => 34, 'accent' => '#5A6B64', 'delta' => 0.9],
    ];

    $atRisk = collect($cards)->take(4)->sum('amount');
    $portfolio = array_sum(array_column($buckets, 'amount'));
@endphp

<div>
    <x-breadcrumb />

    <x-page-header
        title="Past Due & PAR"
        subtitle="Delinquent accounts by aging bucket, sorted by how long they have been overdue.">
        <x-slot:actions>
            <x-btn icon="download">Export</x-btn>
            <x-btn variant="primary" icon="send" @click="$dispatch('open-modal', 'bulk-reminder')">Send Reminders</x-btn>
        </x-slot:actions>
    </x-page-header>

    {{-- Aging buckets. Delta tone is inverted: growth in an aging bucket is bad. --}}
    <section aria-label="Portfolio at risk by aging bucket"
             class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        @foreach($cards as $card)
            <x-stat-card
                :label="$card['label']"
                :value="Format::pesoCompact($card['amount'])"
                :delta="$card['delta']"
                good-direction="down"
                :accent="$card['accent']"
                :hint="number_format($card['accounts']).' accounts'" />
        @endforeach
    </section>

    <div class="mb-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
        <x-card class="lg:col-span-2" title="Portfolio at Risk" subtitle="Share of the outstanding portfolio by aging bucket">
            <x-charts.par-aging height="h-20" />

            <dl class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-5">
                @foreach($buckets as $b)
                    <div>
                        <dt class="flex items-center gap-1.5 text-xs font-medium text-neutral-600">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-[3px]" style="background-color: {{ $b['color'] }}" aria-hidden="true"></span>
                            {{ $b['label'] }}
                        </dt>
                        <dd class="mt-1.5 text-[15px] font-semibold tabular-nums text-neutral-900">{{ Format::pesoCompact($b['amount']) }}</dd>
                        <dd class="text-xs tabular-nums text-neutral-500">{{ Format::percent($b['amount'] / $portfolio * 100) }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-card>

        <x-card title="Risk Summary">
            <dl class="space-y-4">
                <x-kpi label="Total portfolio" :value="Format::peso($portfolio)" />
                <x-kpi label="At risk (1 day +)" :value="Format::peso($atRisk)" tone="danger" />
                <x-kpi label="PAR ratio" :value="Format::percent($atRisk / $portfolio * 100)" tone="danger"
                       hint="Policy ceiling: 5.0%" />
                <x-kpi label="PAR > 30 days" value="5.7%" tone="danger" />
                <x-kpi label="Loan loss provision" :value="Format::peso($atRisk * 0.35)" />
            </dl>

            <div class="mt-5 border-t border-neutral-200 pt-4">
                <x-meter :value="100 - ($atRisk / $portfolio * 100)" label="Healthy portfolio" :thresholds="[90, 95]" />
            </div>
        </x-card>
    </div>

    <x-filter-bar search-label="Search delinquent accounts" search-placeholder="Member name, loan ID, or member ID…">
        <x-select label="Aging bucket" :options="['1–30 days', '31–60 days', '61–90 days', '90+ days', 'Written-off']" placeholder="All buckets" width="w-40" />
        <x-select label="Center" :options="collect($centers)->pluck('name')->all()" placeholder="All centers" width="w-52" />
        <x-select label="Loan officer" :options="collect($officers)->pluck('name')->all()" placeholder="All officers" width="w-44" />
    </x-filter-bar>

    <x-card flush>
        @if(count($accounts) === 0)
            <x-empty-state
                icon="check-circle"
                heading="No delinquent accounts"
                help="Every account in this branch is current. Keep an eye on the 1–30 day bucket to stay ahead of slippage."
                action-label="View loan accounts"
                :action-href="route('loans.accounts')"
                action-icon="file-text" />
        @else
            <x-data-table sort-key="dpd" sort-dir="desc" caption="Delinquent loan accounts sorted by days past due">
                <x-slot:head>
                    <x-th sort="member">Member</x-th>
                    <x-th sort="loan">Loan ID</x-th>
                    <x-th sort="center">Center</x-th>
                    <x-th sort="officer">Officer</x-th>
                    <x-th sort="overdue" align="right">Amount Overdue</x-th>
                    <x-th sort="outstanding" align="right">Outstanding</x-th>
                    <x-th sort="dpd" align="right">Days Past Due</x-th>
                    <x-th sort="bucket">Bucket</x-th>
                    <x-th sort="lastpayment">Last Payment</x-th>
                    <x-th align="right" sr-only>Actions</x-th>
                </x-slot:head>

                @foreach($accounts as $i => $a)
                    <tr data-row data-member="{{ $a['member'] }}" data-loan="{{ $a['loan_id'] }}"
                        data-center="{{ $a['center'] }}" data-officer="{{ $a['officer'] }}"
                        data-overdue="{{ $a['amount_overdue'] }}" data-outstanding="{{ $a['outstanding'] }}"
                        data-dpd="{{ $a['dpd'] }}" data-bucket="{{ $a['bucket'] }}" data-lastpayment="{{ $a['last_payment'] }}"
                        class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">

                        <td data-label="Member" class="px-3 py-2.5">
                            <div class="flex items-center gap-2.5">
                                <x-avatar :name="$a['member']" size="sm" />
                                <div class="min-w-0">
                                    <a href="{{ route('membership.profile', $a['member_id']) }}"
                                       class="block truncate font-medium text-neutral-800 hover:text-primary-700 hover:underline">{{ $a['member'] }}</a>
                                    <span class="block text-xs tabular-nums text-neutral-500">{{ $a['member_id'] }}</span>
                                </div>
                            </div>
                        </td>

                        <td data-label="Loan ID" class="px-3 py-2.5">
                            <a href="{{ route('loans.accounts.show', $a['loan_id']) }}"
                               class="tabular-nums text-primary-700 hover:underline">{{ $a['loan_id'] }}</a>
                        </td>

                        <td data-label="Center" class="px-3 py-2.5 text-neutral-600">{{ $a['center'] }}</td>
                        <td data-label="Officer" class="px-3 py-2.5 text-neutral-600">{{ $a['officer'] }}</td>
                        <td data-label="Amount Overdue" class="px-3 py-2.5 text-right font-medium tabular-nums text-danger">{{ Format::peso($a['amount_overdue']) }}</td>
                        <td data-label="Outstanding" class="px-3 py-2.5 text-right tabular-nums text-neutral-700">{{ Format::peso($a['outstanding']) }}</td>
                        <td data-label="Days Past Due" class="px-3 py-2.5 text-right font-semibold tabular-nums text-danger">{{ $a['dpd'] }}</td>

                        <td data-label="Bucket" class="px-3 py-2.5">
                            <span class="inline-flex items-center gap-1.5 whitespace-nowrap text-[13px] text-neutral-700">
                                <span class="h-2.5 w-2.5 shrink-0 rounded-[3px]"
                                      style="background-color: {{ $ramp[min(4, (int) ceil($a['dpd'] / 30))] }}" aria-hidden="true"></span>
                                {{ $a['bucket'] }}
                            </span>
                        </td>

                        <td data-label="Last Payment" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ Format::date($a['last_payment']) }}</td>

                        <td data-label="" class="px-3 py-2.5 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <x-btn size="sm" icon="send" @click="$dispatch('open-modal', 'send-reminder')">Send Reminder</x-btn>
                                <x-row-actions :label="'More actions for '.$a['loan_id']">
                                    <x-row-action icon="eye" :href="route('loans.accounts.show', $a['loan_id'])">Open loan</x-row-action>
                                    <x-row-action icon="banknote">Record payment</x-row-action>
                                    <x-row-action icon="refresh-cw" :href="route('collections.penalties')">Restructure</x-row-action>
                                    <x-row-action icon="phone">Log a call</x-row-action>
                                    <x-row-action icon="file-x" danger>Write off</x-row-action>
                                </x-row-actions>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>

            <x-pagination :from="1" :to="count($accounts)" :total="434" :current="1" :per-page="16" />
        @endif
    </x-card>

    <x-modal name="send-reminder" title="Send a payment reminder" icon="send" tone="info"
             subtitle="An SMS is sent to the member's registered mobile number.">
        <x-form-field label="Channel" type="select" name="channel" :options="['SMS', 'SMS + call task for officer', 'Call task only']" required />
        <x-form-field class="mt-4" label="Message" type="textarea" name="message" rows="4" required
                      value="Magandang araw po. Ang inyong bayad sa Ledger ay overdue na ng 42 araw (₱1,840.00). Mangyaring makipag-ugnayan sa inyong loan officer. Salamat po."
                      help="160 characters per SMS segment. This message uses 2 segments." />

        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="primary" icon="send">Send Reminder</x-btn>
        </x-slot:footer>
    </x-modal>

    <x-modal name="bulk-reminder" title="Send reminders to all delinquent accounts?" icon="send" tone="warning"
             subtitle="One SMS per account, charged to the branch messaging credits.">
        <dl class="grid grid-cols-2 gap-4 rounded-[12px] border border-neutral-200 bg-neutral-50 p-4">
            <x-kpi label="Accounts in scope" :value="count($accounts)" />
            <x-kpi label="Estimated SMS cost" :value="Format::peso(count($accounts) * 2)" />
        </dl>
        <x-form-field class="mt-4" label="Only include accounts past due by" type="select" name="bucket_scope"
                      :options="['1 day or more', '8 days or more', '31 days or more', '61 days or more']" value="8 days or more" />

        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="primary" icon="send">Send {{ count($accounts) }} Reminders</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
