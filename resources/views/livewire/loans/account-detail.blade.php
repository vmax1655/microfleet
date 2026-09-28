@php
    use App\Support\Format;

    $trail = [
        ['label' => 'Loan Management', 'route' => 'loans.applications'],
        ['label' => 'Loan Accounts', 'route' => 'loans.accounts'],
        ['label' => $loan['id']],
    ];

    $remaining = max(0, $totals['due'] - $totals['paid']);
    $nextInstalment = collect($schedule)->firstWhere('status', '!=', 'Paid');
@endphp

<div>
    <x-breadcrumb :trail="$trail" />

    <x-page-header
        :title="$loan['id']"
        :subtitle="$loan['member'].' · '.$loan['product'].' · '.$loan['center']"
        :back="route('loans.accounts')"
        back-label="Loan accounts">
        <x-slot:actions>
            <x-btn icon="printer">Print statement</x-btn>
            <x-btn icon="send">Send reminder</x-btn>
            <x-btn variant="primary" icon="banknote" @click="$dispatch('open-modal', 'record-payment')">Record Payment</x-btn>
        </x-slot:actions>
    </x-page-header>

    {{-- ================================================================ summary strip --}}
    <x-card class="mb-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <x-avatar :name="$loan['member']" size="lg" />
                <div class="min-w-0">
                    <a href="{{ route('membership.profile', $loan['member_id']) }}"
                       class="block text-[15px] font-semibold text-neutral-800 hover:text-primary-700 hover:underline">{{ $loan['member'] }}</a>
                    <p class="text-xs tabular-nums text-neutral-500">{{ $loan['member_id'] }} · {{ $loan['branch'] }}</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <x-status-badge :status="$loan['status']" />
                @if($loan['dpd'] > 0)
                    <span class="inline-flex items-center gap-1 rounded-full border border-[#F5B5AE] bg-[#FEECEA] px-2.5 py-0.5 text-xs font-medium tabular-nums text-danger">
                        <x-icon name="alert-triangle" class="h-3.5 w-3.5" />
                        {{ $loan['dpd'] }} days past due
                    </span>
                @endif
            </div>
        </div>

        <dl class="mt-5 grid grid-cols-2 gap-x-6 gap-y-4 border-t border-neutral-200 pt-5 sm:grid-cols-4 xl:grid-cols-8">
            <x-kpi label="Principal" :value="Format::peso($loan['principal'])" />
            <x-kpi label="Interest Rate" :value="$loan['rate'].'% '.$loan['method']" />
            <x-kpi label="Term" :value="$loan['term'].' months'" :hint="$loan['frequency']" />
            <x-kpi label="Disbursed" :value="Format::date($loan['disbursed_on'])" />
            <x-kpi label="Maturity" :value="Format::date($loan['maturity_on'])" />
            <x-kpi label="Outstanding" :value="Format::peso($loan['outstanding'])" />
            <x-kpi label="Next Due" :value="Format::date($loan['next_due'])"
                   :hint="$loan['next_due'] ? Format::peso($loan['next_due_amount']) : null" />
            <x-kpi label="Days Past Due" :value="$loan['dpd'] > 0 ? $loan['dpd'] : '0'"
                   :tone="$loan['dpd'] > 0 ? 'danger' : 'default'" />
        </dl>

        {{-- Repayment progress --}}
        <div class="mt-5 border-t border-neutral-200 pt-5">
            <div class="mb-2 flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-[13px] font-semibold uppercase tracking-wide text-neutral-500">Repayment progress</h2>
                <p class="text-[13px] tabular-nums text-neutral-600">
                    <span class="font-semibold text-neutral-900">{{ Format::peso($totals['paid']) }}</span>
                    paid of {{ Format::peso($totals['due']) }}
                    <span class="text-neutral-400">·</span>
                    <span class="font-medium text-neutral-700">{{ Format::peso($remaining) }} remaining</span>
                </p>
            </div>
            <x-meter :value="$loan['progress']" tone="primary" size="lg" label="Paid to date" />
        </div>
    </x-card>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-4">

        {{-- ============================================================ amortization --}}
        <x-card class="xl:col-span-3" flush
                title="Amortization Schedule"
                :subtitle="count($schedule).' instalments · '.$loan['frequency'].' · '.$loan['method'].' method'">
            <x-slot:actions>
                <x-btn size="sm" icon="printer">Print</x-btn>
                <x-btn size="sm" icon="file-spreadsheet">Excel</x-btn>
            </x-slot:actions>

            <div class="max-h-[640px] overflow-y-auto">
                <x-data-table sort-key="no" caption="Full amortization schedule for this loan" :stack="false">
                    <x-slot:head>
                        <x-th sort="no" align="right" width="56px">#</x-th>
                        <x-th sort="duedate">Due Date</x-th>
                        <x-th sort="beginning" align="right">Beginning Balance</x-th>
                        <x-th sort="principal" align="right">Principal</x-th>
                        <x-th sort="interest" align="right">Interest</x-th>
                        <x-th sort="totaldue" align="right">Total Due</x-th>
                        <x-th sort="paid" align="right">Amount Paid</x-th>
                        <x-th sort="datepaid">Date Paid</x-th>
                        <x-th sort="ending" align="right">Ending Balance</x-th>
                        <x-th sort="status">Status</x-th>
                    </x-slot:head>

                    @foreach($schedule as $row)
                        <tr data-row
                            data-no="{{ $row['no'] }}" data-duedate="{{ $row['due_date'] }}"
                            data-beginning="{{ $row['beginning'] }}" data-principal="{{ $row['principal'] }}"
                            data-interest="{{ $row['interest'] }}" data-totaldue="{{ $row['total_due'] }}"
                            data-paid="{{ $row['amount_paid'] }}" data-datepaid="{{ $row['date_paid'] }}"
                            data-ending="{{ $row['ending'] }}" data-status="{{ $row['status'] }}"
                            @class([
                                'transition-colors',
                                'bg-primary-50' => $row['status'] === 'Paid',
                                'border-l-[3px] border-l-danger bg-white' => $row['status'] === 'Overdue',
                                'hover:bg-primary-50' => $row['status'] !== 'Paid',
                            ])>

                            <td class="px-3 py-2 text-right tabular-nums text-neutral-500">{{ $row['no'] }}</td>
                            <td class="whitespace-nowrap px-3 py-2 tabular-nums text-neutral-700">{{ Format::date($row['due_date']) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums text-neutral-600">{{ Format::peso($row['beginning']) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums text-neutral-700">{{ Format::peso($row['principal']) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums text-neutral-700">{{ Format::peso($row['interest']) }}</td>
                            <td class="px-3 py-2 text-right font-medium tabular-nums text-neutral-800">{{ Format::peso($row['total_due']) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums {{ $row['amount_paid'] > 0 ? 'text-success' : 'text-neutral-400' }}">
                                {{ $row['amount_paid'] > 0 ? Format::peso($row['amount_paid']) : '—' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 tabular-nums text-neutral-600">{{ $row['date_paid'] ? Format::date($row['date_paid']) : '—' }}</td>
                            <td class="px-3 py-2 text-right tabular-nums text-neutral-600">{{ Format::peso($row['ending']) }}</td>
                            <td class="px-3 py-2"><x-status-badge :status="$row['status']" /></td>
                        </tr>
                    @endforeach

                    <x-slot:foot>
                        <tr>
                            <td class="px-3 py-2.5"></td>
                            <td class="px-3 py-2.5 text-[13px] font-semibold text-neutral-700">Totals</td>
                            <td class="px-3 py-2.5"></td>
                            <td class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">{{ Format::peso($totals['principal']) }}</td>
                            <td class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">{{ Format::peso($totals['interest']) }}</td>
                            <td class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">{{ Format::peso($totals['due']) }}</td>
                            <td class="px-3 py-2.5 text-right font-semibold tabular-nums text-success">{{ Format::peso($totals['paid']) }}</td>
                            <td class="px-3 py-2.5"></td>
                            <td class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">{{ Format::peso($remaining) }}</td>
                            <td class="px-3 py-2.5"></td>
                        </tr>
                    </x-slot:foot>
                </x-data-table>
            </div>
        </x-card>

        {{-- ============================================================ side panel --}}
        <aside class="space-y-5">
            <x-card title="Loan Officer">
                <div class="flex items-center gap-3">
                    <x-avatar :name="$loan['officer']" size="md" tone="accent" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-neutral-800">{{ $loan['officer'] }}</p>
                        <p class="text-xs text-neutral-500">{{ $loan['branch'] }}</p>
                    </div>
                </div>
                <dl class="mt-4 space-y-3 border-t border-neutral-200 pt-4">
                    <x-kpi label="Center" :value="$loan['center']" :mono="false" />
                    <x-kpi label="Collection day" value="Monday, 8:00 AM" :mono="false" />
                </dl>
            </x-card>

            <x-card title="Collateral">
                <dl class="space-y-3">
                    <x-kpi label="Type" :value="$loan['collateral']" :mono="false" />
                    <x-kpi label="Appraised value" :value="Format::peso($loan['principal'] * 1.4)" />
                    <x-kpi label="Documents" value="OR/CR on file" :mono="false" />
                </dl>
            </x-card>

            <x-card title="Co-maker">
                <div class="flex items-center gap-3">
                    <x-avatar :name="$loan['comaker']" size="md" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-neutral-800">{{ $loan['comaker'] }}</p>
                        <p class="text-xs tabular-nums text-neutral-500">{{ $loan['comaker_contact'] }}</p>
                    </div>
                </div>
                <dl class="mt-4 space-y-3 border-t border-neutral-200 pt-4">
                    <x-kpi label="Relationship" value="Sibling" :mono="false" />
                    <x-kpi label="Standing" value="Active · no arrears" :mono="false" />
                </dl>
            </x-card>

            <x-card>
                <h2 class="text-[15px] font-semibold text-neutral-800">Next instalment</h2>
                @if($nextInstalment)
                    <p class="mt-3 text-2xl font-semibold tabular-nums text-neutral-900">{{ Format::peso($nextInstalment['total_due']) }}</p>
                    <p class="mt-1 text-[13px] tabular-nums text-neutral-500">
                        Instalment #{{ $nextInstalment['no'] }} due {{ Format::date($nextInstalment['due_date']) }}
                    </p>
                    <div class="mt-3">
                        <x-status-badge :status="$nextInstalment['status']" />
                    </div>
                @else
                    <p class="mt-3 text-[13px] text-neutral-500">This loan is fully paid. No further instalments are due.</p>
                @endif

                <x-btn class="mt-4 w-full" icon="banknote" @click="$dispatch('open-modal', 'record-payment')">Record Payment</x-btn>
            </x-card>
        </aside>
    </div>

    {{-- ================================================================ payment modal --}}
    <x-modal name="record-payment" title="Record Payment" icon="banknote"
             :subtitle="$loan['id'].' · '.$loan['member']">
        <div x-data="paymentForm({{ $loan['outstanding'] }}, {{ $nextInstalment['total_due'] ?? 0 }})" class="space-y-4">

            <dl class="grid grid-cols-2 gap-3 rounded-[8px] border border-neutral-200 bg-neutral-50 p-3.5">
                <x-kpi label="Outstanding balance" :value="Format::peso($loan['outstanding'])" />
                <x-kpi label="Amount due now" :value="Format::peso($nextInstalment['total_due'] ?? 0)" />
            </dl>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form-field label="Amount collected" type="number" name="amount" prefix="₱" tabular required
                              x-model.number="amount" step="0.01" min="0" />
                <x-form-field label="Payment date" type="date" name="paid_on" value="2026-09-09" required tabular />
                <x-form-field label="Method" type="select" name="method" required
                              :options="['Cash', 'GCash', 'Bank Transfer']" x-model="method" />
                <x-form-field label="OR number" name="or_no" value="OR-2026-0084531" tabular required
                              help="Pre-filled from the next unused receipt." />
            </div>

            <div x-show="method === 'GCash'" x-cloak>
                <x-form-field label="GCash reference number" name="gcash_ref" tabular
                              placeholder="e.g. 0028 4471 9930"
                              help="Required so the treasury team can reconcile the transfer." />
            </div>

            <x-form-field label="Notes" type="textarea" name="notes" rows="2"
                          placeholder="Optional — e.g. collected at the Monday center meeting" />

            {{-- Live remaining-balance preview --}}
            <div class="flex items-center justify-between rounded-[8px] border p-3.5"
                 :class="overpaid ? 'border-[#F5B5AE] bg-[#FEECEA]' : 'border-primary-200 bg-primary-50'">
                <span class="text-[13px] font-medium" :class="overpaid ? 'text-danger' : 'text-primary-800'">
                    Remaining balance after payment
                </span>
                <span class="text-[17px] font-semibold tabular-nums"
                      :class="overpaid ? 'text-danger' : 'text-primary-800'"
                      x-text="peso(remaining)">₱0.00</span>
            </div>

            <p x-show="overpaid" x-cloak class="flex items-start gap-1.5 text-xs text-danger">
                <x-icon name="alert-circle" class="mt-px h-3.5 w-3.5 shrink-0" />
                <span>Amount exceeds the outstanding balance. The excess will be credited to the member's savings account.</span>
            </p>
        </div>

        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="primary" icon="check">Post Payment</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
