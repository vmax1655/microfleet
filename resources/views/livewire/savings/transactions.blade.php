@php
    use App\Support\Format;

    $net = $session['deposits'] - $session['withdrawals'];
    $lookup = $accounts[0];
@endphp

{{--
    Teller entry screen. The form sits first in the DOM so it is the first thing
    reachable on a phone; the posted-transactions table follows underneath and
    only moves beside it from lg upwards.
--}}
<div>
    <x-breadcrumb />

    <x-page-header
        title="Deposits & Withdrawals"
        :subtitle="'Over-the-counter savings transactions for '.Format::date('2026-09-09')">
        <x-slot:actions>
            <x-btn icon="printer">Print session</x-btn>
            <x-btn icon="calculator" :href="route('savings.blotter')">Teller blotter</x-btn>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-5">

        {{-- ============================================================ entry form --}}
        <div class="lg:col-span-2">
            <x-card title="New Transaction" subtitle="Look up the account, then post the entry">
                <form class="space-y-5"
                      x-data="{ type: 'Deposit', amount: 500, method: 'Cash', balance: {{ $lookup['balance'] }} }"
                      onsubmit="return false;">

                    {{-- Account lookup --}}
                    <div>
                        <label for="account-lookup" class="mb-1 block text-[13px] font-medium text-neutral-700">
                            Account lookup <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 grid w-10 place-items-center text-neutral-400">
                                <x-icon name="search" />
                            </span>
                            <input id="account-lookup"
                                   type="search"
                                   value="{{ $lookup['account_no'] }}"
                                   placeholder="Account number, member name, or member ID"
                                   class="h-11 w-full rounded-[8px] border border-neutral-300 bg-white pl-10 pr-3 text-sm tabular-nums text-neutral-800 placeholder:text-neutral-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-600/30">
                        </div>
                        <p class="mt-1 text-xs text-neutral-500">Scan the passbook barcode or type any part of the member's name.</p>
                    </div>

                    {{-- Resolved account card --}}
                    <div class="rounded-[12px] border border-primary-200 bg-primary-50 p-4">
                        <div class="flex items-center gap-3">
                            <x-avatar :name="$lookup['member']" size="md" tone="inverse" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-primary-900">{{ $lookup['member'] }}</p>
                                <p class="truncate text-xs tabular-nums text-primary-800/80">
                                    {{ $lookup['account_no'] }} · {{ $lookup['product'] }}
                                </p>
                            </div>
                            <x-status-badge :status="$lookup['status']" />
                        </div>
                        <dl class="mt-3 grid grid-cols-2 gap-3 border-t border-primary-200 pt-3">
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-primary-800/70">Current balance</dt>
                                <dd class="mt-0.5 text-[15px] font-semibold tabular-nums text-primary-900">{{ Format::peso($lookup['balance']) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-primary-800/70">Balance after</dt>
                                <dd class="mt-0.5 text-[15px] font-semibold tabular-nums text-primary-900"
                                    x-text="window.LedgerFormat.peso(type === 'Deposit' ? balance + Number(amount || 0) : balance - Number(amount || 0))">
                                    {{ Format::peso($lookup['balance']) }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Transaction type --}}
                    <fieldset>
                        <legend class="mb-1.5 block text-[13px] font-medium text-neutral-700">
                            Transaction type <span class="text-danger" aria-hidden="true">*</span>
                        </legend>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach(['Deposit' => 'arrow-down', 'Withdrawal' => 'arrow-up'] as $label => $icon)
                                <label class="flex cursor-pointer items-center justify-center gap-2 rounded-[8px] border px-3 py-2.5 text-sm font-medium transition-colors"
                                       :class="type === '{{ $label }}'
                                           ? 'border-primary-600 bg-primary-50 text-primary-800'
                                           : 'border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50'">
                                    <input type="radio" name="txn_type" value="{{ $label }}" x-model="type" class="sr-only">
                                    <x-icon :name="$icon" />
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    {{-- Amount --}}
                    <div>
                        <label for="txn-amount" class="mb-1 block text-[13px] font-medium text-neutral-700">
                            Amount <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 grid w-9 place-items-center text-base text-neutral-500">₱</span>
                            <input id="txn-amount"
                                   type="number"
                                   inputmode="decimal"
                                   step="0.01"
                                   min="0"
                                   x-model.number="amount"
                                   class="h-12 w-full rounded-[8px] border border-neutral-300 bg-white pl-9 pr-3 text-right text-lg font-semibold tabular-nums text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-600/30">
                        </div>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach([100, 200, 500, 1000, 2000] as $quick)
                                <button type="button" @click="amount = {{ $quick }}"
                                        class="rounded-full border border-neutral-300 px-2.5 py-1 text-xs font-medium tabular-nums text-neutral-700 hover:bg-neutral-50">
                                    ₱{{ number_format($quick) }}
                                </button>
                            @endforeach
                        </div>
                        <p x-show="type === 'Withdrawal' && Number(amount || 0) > balance - 2500" x-cloak
                           class="mt-1.5 flex items-start gap-1 text-xs text-danger">
                            <x-icon name="alert-circle" class="mt-px h-3.5 w-3.5 shrink-0" />
                            <span>Exceeds the withdrawable balance — ₱2,500.00 is held as loan security.</span>
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form-field label="OR number" name="or_no" value="OR-2026-0084912" required tabular />
                        <x-form-field label="Method" type="select" name="method" required
                                      :options="['Cash', 'GCash', 'Bank Transfer']" x-model="method" />
                    </div>

                    <div x-show="method === 'GCash'" x-cloak>
                        <x-form-field label="GCash reference number" name="gcash_ref" tabular
                                      placeholder="e.g. 0028 4471 9930" />
                    </div>

                    <x-form-field label="Remarks" type="textarea" name="remarks" rows="2"
                                  placeholder="Optional — e.g. deposit collected at the Monday center meeting" />

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <x-btn size="lg" class="sm:flex-1" icon="x">Clear</x-btn>
                        <x-btn size="lg" variant="primary" class="sm:flex-1" icon="check"
                               @click="$dispatch('open-modal', 'confirm-transaction')">Post Transaction</x-btn>
                    </div>
                </form>
            </x-card>
        </div>

        {{-- ============================================================ session log --}}
        <div class="lg:col-span-3">
            <section aria-label="Session totals" class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-stat-card label="Deposits Posted" :value="Format::peso($session['deposits'])" icon="arrow-down"
                             :hint="collect($today)->where('type', 'Deposit')->count().' transactions'" />
                <x-stat-card label="Withdrawals Posted" :value="Format::peso($session['withdrawals'])" icon="arrow-up"
                             :hint="collect($today)->where('type', 'Withdrawal')->count().' transactions'" />
                <x-stat-card label="Net Cash Movement" :value="Format::peso($net)" icon="wallet"
                             :hint="$session['count'].' transactions this session'" />
            </section>

            <x-card flush title="Posted Today" subtitle="Transactions you have posted in this session">
                <x-slot:actions>
                    <x-btn size="sm" icon="printer">Print</x-btn>
                </x-slot:actions>

                @if(count($today) === 0)
                    <x-empty-state
                        compact
                        icon="receipt"
                        heading="Nothing posted yet"
                        help="Transactions you post in this session appear here, with a running total."
                        action-label="Open teller blotter"
                        :action-href="route('savings.blotter')"
                        action-icon="calculator" />
                @else
                    <x-data-table sort-key="time" sort-dir="desc" caption="Transactions posted in this teller session">
                        <x-slot:head>
                            <x-th sort="time">Time</x-th>
                            <x-th sort="or">OR Number</x-th>
                            <x-th sort="account">Account</x-th>
                            <x-th sort="member">Member</x-th>
                            <x-th sort="type">Type</x-th>
                            <x-th sort="amount" align="right">Amount</x-th>
                            <x-th sort="method">Method</x-th>
                            <x-th align="right" sr-only>Actions</x-th>
                        </x-slot:head>

                        @foreach($today as $i => $t)
                            <tr data-row data-time="{{ $t['time'] }}" data-or="{{ $t['or_no'] }}"
                                data-account="{{ $t['account_no'] }}" data-member="{{ $t['member'] }}"
                                data-type="{{ $t['type'] }}" data-amount="{{ $t['amount'] }}" data-method="{{ $t['method'] }}"
                                class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">

                                <td data-label="Time" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ $t['time'] }}</td>
                                <td data-label="OR Number" class="px-3 py-2.5 tabular-nums text-neutral-700">{{ $t['or_no'] }}</td>
                                <td data-label="Account" class="px-3 py-2.5">
                                    <a href="{{ route('savings.accounts.show', $t['account_no']) }}"
                                       class="tabular-nums text-primary-700 hover:underline">{{ $t['account_no'] }}</a>
                                </td>
                                <td data-label="Member" class="px-3 py-2.5 text-neutral-700">{{ $t['member'] }}</td>
                                <td data-label="Type" class="px-3 py-2.5">
                                    <x-status-badge :status="$t['type']" :tone="$t['type'] === 'Withdrawal' ? 'warning' : 'success'">{{ $t['type'] }}</x-status-badge>
                                </td>
                                <td data-label="Amount" class="px-3 py-2.5 text-right font-medium tabular-nums {{ $t['type'] === 'Withdrawal' ? 'text-danger' : 'text-success' }}">
                                    {{ $t['type'] === 'Withdrawal' ? '−' : '+' }}{{ Format::peso($t['amount']) }}
                                </td>
                                <td data-label="Method" class="px-3 py-2.5 text-neutral-600">{{ $t['method'] }}</td>
                                <td data-label="" class="px-3 py-2.5 text-right">
                                    <x-row-actions :label="'Actions for '.$t['or_no']">
                                        <x-row-action icon="printer">Reprint receipt</x-row-action>
                                        <x-row-action icon="eye" :href="route('savings.accounts.show', $t['account_no'])">Open account</x-row-action>
                                        <x-row-action icon="x" danger>Void transaction</x-row-action>
                                    </x-row-actions>
                                </td>
                            </tr>
                        @endforeach

                        <x-slot:foot>
                            <tr>
                                <td data-label="" class="px-3 py-2.5 text-[13px] font-semibold text-neutral-700" colspan="5">
                                    Running session total
                                </td>
                                <td data-label="Amount" class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">
                                    {{ Format::peso($net) }}
                                </td>
                                <td data-label="" class="px-3 py-2.5" colspan="2"></td>
                            </tr>
                        </x-slot:foot>
                    </x-data-table>
                @endif
            </x-card>
        </div>
    </div>

    <x-modal name="confirm-transaction" title="Post this transaction?" icon="banknote" tone="warning"
             subtitle="A receipt is issued immediately and the savings ledger is updated.">
        <dl class="grid grid-cols-2 gap-4 rounded-[12px] border border-neutral-200 bg-neutral-50 p-4">
            <x-kpi label="Account" :value="$lookup['account_no']" />
            <x-kpi label="Member" :value="$lookup['member']" :mono="false" />
            <x-kpi label="Current balance" :value="Format::peso($lookup['balance'])" />
            <x-kpi label="OR number" value="OR-2026-0084912" />
        </dl>

        <p class="mt-4 text-sm text-neutral-600">
            Verify the amount against the cash counted before posting. Voiding a posted transaction requires a branch
            manager and is written to the audit log.
        </p>

        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="primary" icon="check">Post Transaction</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
