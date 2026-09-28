@php
    use App\Support\Format;

    $trail = [
        ['label' => 'Savings & Treasury', 'route' => 'savings.accounts'],
        ['label' => 'Savings Accounts', 'route' => 'savings.accounts'],
        ['label' => $account['account_no']],
    ];
@endphp

<div>
    <x-breadcrumb :trail="$trail" />

    <x-page-header
        :title="$account['account_no']"
        :subtitle="$account['member'].' · '.$account['product'].' · '.$account['branch']"
        :back="route('savings.accounts')"
        back-label="Savings accounts">
        <x-slot:actions>
            <x-btn icon="printer">Print passbook</x-btn>
            <x-btn icon="arrow-up" :href="route('savings.transactions')">Withdraw</x-btn>
            <x-btn variant="primary" icon="arrow-down" :href="route('savings.transactions')">Post Deposit</x-btn>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <x-avatar :name="$account['member']" size="lg" />
                <div class="min-w-0">
                    <a href="{{ route('membership.profile', $account['member_id']) }}"
                       class="block text-[15px] font-semibold text-neutral-800 hover:text-primary-700 hover:underline">{{ $account['member'] }}</a>
                    <p class="text-xs tabular-nums text-neutral-500">{{ $account['member_id'] }} · {{ $account['center'] }}</p>
                </div>
            </div>
            <x-status-badge :status="$account['status']" />
        </div>

        <dl class="mt-5 grid grid-cols-2 gap-x-6 gap-y-4 border-t border-neutral-200 pt-5 sm:grid-cols-3 lg:grid-cols-6">
            <x-kpi label="Current Balance" :value="Format::peso($account['balance'])" />
            <x-kpi label="Product" :value="$account['product']" :mono="false" />
            <x-kpi label="Interest Rate" :value="$account['rate'].'% p.a.'" />
            <x-kpi label="Opened" :value="Format::date($account['opened'])" />
            <x-kpi label="Last Transaction" :value="Format::date($account['last_txn'])" />
            <x-kpi label="Interest Earned YTD" :value="Format::peso($totals['interest'])" />
        </dl>
    </x-card>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-4">
        <x-card class="lg:col-span-3" flush
                title="Transaction Ledger"
                :subtitle="count($ledger).' transactions with running balance'">
            <x-slot:actions>
                <x-btn size="sm" icon="printer">Print</x-btn>
                <x-btn size="sm" icon="file-spreadsheet">Excel</x-btn>
            </x-slot:actions>

            @if(count($ledger) === 0)
                <x-empty-state
                    icon="receipt"
                    heading="No transactions yet"
                    help="Deposits made at center meetings and over the counter will appear here."
                    action-label="Post Deposit"
                    :action-href="route('savings.transactions')"
                    action-icon="arrow-down" />
            @else
                <x-data-table sort-key="date" sort-dir="desc" caption="Savings transaction ledger with running balance">
                    <x-slot:head>
                        <x-th sort="date">Date</x-th>
                        <x-th sort="or">OR Number</x-th>
                        <x-th sort="type">Type</x-th>
                        <x-th>Description</x-th>
                        <x-th sort="debit" align="right">Withdrawal</x-th>
                        <x-th sort="credit" align="right">Deposit</x-th>
                        <x-th sort="balance" align="right">Running Balance</x-th>
                        <x-th sort="teller">Teller</x-th>
                    </x-slot:head>

                    @foreach($ledger as $i => $t)
                        <tr data-row data-date="{{ $t['date'] }}" data-or="{{ $t['or_no'] }}" data-type="{{ $t['type'] }}"
                            data-debit="{{ $t['debit'] }}" data-credit="{{ $t['credit'] }}"
                            data-balance="{{ $t['balance'] }}" data-teller="{{ $t['teller'] }}"
                            class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">

                            <td data-label="Date" class="px-3 py-2.5 tabular-nums text-neutral-700">{{ Format::date($t['date']) }}</td>
                            <td data-label="OR Number" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ $t['or_no'] }}</td>

                            <td data-label="Type" class="px-3 py-2.5">
                                @php
                                    $tone = match ($t['type']) {
                                        'Withdrawal' => 'warning',
                                        'Interest' => 'info',
                                        default => 'success',
                                    };
                                @endphp
                                <x-status-badge :status="$t['type']" :tone="$tone">{{ $t['type'] }}</x-status-badge>
                            </td>

                            <td data-label="Description" class="px-3 py-2.5 text-neutral-600">{{ $t['description'] }}</td>
                            <td data-label="Withdrawal" class="px-3 py-2.5 text-right tabular-nums {{ $t['debit'] ? 'text-danger' : 'text-neutral-400' }}">
                                {{ $t['debit'] ? Format::peso($t['debit']) : '—' }}
                            </td>
                            <td data-label="Deposit" class="px-3 py-2.5 text-right tabular-nums {{ $t['credit'] ? 'text-success' : 'text-neutral-400' }}">
                                {{ $t['credit'] ? Format::peso($t['credit']) : '—' }}
                            </td>
                            <td data-label="Running Balance" class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800">{{ Format::peso($t['balance']) }}</td>
                            <td data-label="Teller" class="px-3 py-2.5 text-neutral-600">{{ $t['teller'] }}</td>
                        </tr>
                    @endforeach

                    <x-slot:foot>
                        <tr>
                            <td data-label="" class="px-3 py-2.5 text-[13px] font-semibold text-neutral-700" colspan="4">Period totals</td>
                            <td data-label="Withdrawal" class="px-3 py-2.5 text-right font-semibold tabular-nums text-danger">{{ Format::peso($totals['withdrawals']) }}</td>
                            <td data-label="Deposit" class="px-3 py-2.5 text-right font-semibold tabular-nums text-success">{{ Format::peso($totals['deposits']) }}</td>
                            <td data-label="Running Balance" class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">{{ Format::peso($account['balance']) }}</td>
                            <td data-label="" class="px-3 py-2.5"></td>
                        </tr>
                    </x-slot:foot>
                </x-data-table>

                <x-pagination :from="1" :to="count($ledger)" :total="count($ledger)" :current="1" />
            @endif
        </x-card>

        <aside class="space-y-5">
            <x-card title="Account Summary">
                <dl class="space-y-4">
                    <x-kpi label="Total deposits" :value="Format::peso($totals['deposits'])" tone="success" />
                    <x-kpi label="Total withdrawals" :value="Format::peso($totals['withdrawals'])" tone="danger" />
                    <x-kpi label="Interest credited" :value="Format::peso($totals['interest'])" />
                    <x-kpi label="Maintaining balance" :value="Format::peso(500)" hint="Below this, dormancy fees apply" />
                </dl>
            </x-card>

            <x-card title="Hold-out & Liens">
                <div class="rounded-[8px] border border-[#F5D28A] bg-[#FEF0D6] p-3.5">
                    <div class="flex gap-2.5">
                        <x-icon name="lock" class="mt-0.5 shrink-0 text-warning" />
                        <div>
                            <p class="text-[13px] font-medium text-warning">₱2,500.00 held as loan security</p>
                            <p class="mt-0.5 text-[13px] text-warning/90">
                                Released when LN-2026-7001 is fully paid.
                            </p>
                        </div>
                    </div>
                </div>
                <p class="mt-3 text-[13px] tabular-nums text-neutral-600">
                    Available for withdrawal:
                    <span class="font-semibold text-neutral-900">{{ Format::peso(max(0, $account['balance'] - 2500)) }}</span>
                </p>
            </x-card>

            <x-card title="Signatories">
                <ul class="space-y-3">
                    <li class="flex items-center gap-3">
                        <x-avatar :name="$account['member']" size="sm" />
                        <div class="min-w-0">
                            <p class="truncate text-[13px] font-medium text-neutral-800">{{ $account['member'] }}</p>
                            <p class="text-xs text-neutral-500">Primary account holder</p>
                        </div>
                    </li>
                    <li class="flex items-center gap-3">
                        <x-avatar name="Rodel A. Marquez" size="sm" tone="neutral" />
                        <div class="min-w-0">
                            <p class="truncate text-[13px] font-medium text-neutral-800">Rodel A. Marquez</p>
                            <p class="text-xs text-neutral-500">Beneficiary · Spouse</p>
                        </div>
                    </li>
                </ul>
            </x-card>
        </aside>
    </div>
</div>
