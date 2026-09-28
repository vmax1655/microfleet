@php use App\Support\Format; @endphp

<div x-data="denominationGrid(@js($denominations), {{ $blotter['expected'] }})">
    <x-breadcrumb />

    <x-page-header
        title="Teller Blotter"
        :subtitle="'Cash position for '.Format::date($blotter['date']).' · '.$blotter['teller'].' · '.$blotter['branch']">
        <x-slot:actions>
            <x-btn icon="printer">Print blotter</x-btn>
            <x-btn icon="download">Export</x-btn>
            <x-btn variant="primary" icon="lock" @click="$dispatch('open-modal', 'close-day')">Close Day</x-btn>
        </x-slot:actions>
    </x-page-header>

    {{-- ================================================================ cash position --}}
    <x-card class="mb-5" title="Daily Cash Position" subtitle="Every movement through the teller drawer today">
        <dl class="grid grid-cols-2 gap-x-6 gap-y-5 sm:grid-cols-3 lg:grid-cols-6">
            <x-kpi label="Beginning Cash" :value="Format::peso($blotter['beginning'])" />
            <x-kpi label="Total Collections" :value="Format::peso($blotter['collections'])" tone="success" />
            <x-kpi label="Total Deposits" :value="Format::peso($blotter['deposits'])" tone="success" />
            <x-kpi label="Total Disbursements" :value="Format::peso($blotter['disbursements'])" tone="danger" />
            <x-kpi label="Total Withdrawals" :value="Format::peso($blotter['withdrawals'])" tone="danger" />
            <x-kpi label="Expected Cash on Hand" :value="Format::peso($blotter['expected'])" />
        </dl>

        {{-- Reconciliation --}}
        <div class="mt-6 grid grid-cols-1 gap-4 border-t border-neutral-200 pt-5 sm:grid-cols-3">
            <div class="rounded-[12px] border border-neutral-200 bg-neutral-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-neutral-500">Expected cash on hand</p>
                <p class="mt-1.5 text-xl font-semibold tabular-nums text-neutral-900">{{ Format::peso($blotter['expected']) }}</p>
                <p class="mt-1 text-xs text-neutral-500">Beginning + collections + deposits − disbursements − withdrawals</p>
            </div>

            <div class="rounded-[12px] border border-neutral-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-neutral-500">Actual count</p>
                <p class="mt-1.5 text-xl font-semibold tabular-nums text-neutral-900" x-text="peso(total())">
                    {{ Format::peso($blotter['actual']) }}
                </p>
                <p class="mt-1 text-xs text-neutral-500">From the denomination breakdown below</p>
            </div>

            {{-- Variance is danger-coloured whenever it is non-zero, in either direction. --}}
            <div class="rounded-[12px] border p-4 transition-colors"
                 :class="Math.abs(variance()) > 0.005
                     ? 'border-[#F5B5AE] bg-[#FEECEA]'
                     : 'border-[#A6E0C1] bg-[#E7F6EE]'">
                <p class="text-xs font-medium uppercase tracking-wide"
                   :class="Math.abs(variance()) > 0.005 ? 'text-danger' : 'text-success'">Variance</p>
                <p class="mt-1.5 text-xl font-semibold tabular-nums"
                   :class="Math.abs(variance()) > 0.005 ? 'text-danger' : 'text-success'"
                   x-text="peso(variance())">₱0.00</p>
                <p class="mt-1 text-xs"
                   :class="Math.abs(variance()) > 0.005 ? 'text-danger' : 'text-success'"
                   x-text="Math.abs(variance()) > 0.005
                       ? (variance() > 0 ? 'Overage — investigate before closing the day.' : 'Shortage — investigate before closing the day.')
                       : 'Balanced. Safe to close the day.'">
                </p>
            </div>
        </div>
    </x-card>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

        {{-- ============================================================ denominations --}}
        <x-card class="lg:col-span-2" title="Denomination Breakdown" subtitle="Count the drawer and enter each denomination">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <caption class="sr-only">Cash denomination count</caption>
                    <thead class="bg-neutral-100">
                        <tr>
                            <th scope="col" class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-neutral-600">Denomination</th>
                            <th scope="col" class="px-3 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-neutral-600">Count</th>
                            <th scope="col" class="px-3 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-neutral-600">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200">
                        @foreach($denominations as $di => $d)
                            <tr class="{{ $di % 2 ? 'bg-neutral-50' : '' }}">
                                <td class="px-3 py-2">
                                    <label for="denom-{{ $di }}" class="font-medium tabular-nums text-neutral-800">{{ $d['label'] }}</label>
                                </td>
                                <td class="px-3 py-2">
                                    <input id="denom-{{ $di }}"
                                           type="number"
                                           inputmode="numeric"
                                           min="0"
                                           step="1"
                                           x-model.number="rows[{{ $di }}].count"
                                           value="{{ $d['count'] }}"
                                           class="h-9 w-full rounded-[8px] border border-neutral-300 bg-white px-2 text-right text-sm tabular-nums text-neutral-800 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-600/30 sm:w-28 sm:ml-auto sm:block">
                                </td>
                                <td class="px-3 py-2 text-right font-medium tabular-nums text-neutral-800"
                                    x-text="peso(lineTotal(rows[{{ $di }}]))">
                                    {{ Format::peso($d['value'] * $d['count']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-neutral-300 bg-neutral-50">
                        <tr>
                            <td class="px-3 py-3 text-[13px] font-semibold text-neutral-700">Total counted</td>
                            <td class="px-3 py-3 text-right text-[13px] tabular-nums text-neutral-600"
                                x-text="rows.reduce((s, r) => s + Number(r.count || 0), 0) + ' pcs'"></td>
                            <td class="px-3 py-3 text-right text-[15px] font-semibold tabular-nums text-neutral-900" x-text="peso(total())">
                                {{ Format::peso($blotter['actual']) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-card>

        {{-- ============================================================ side --}}
        <aside class="space-y-5">
            <x-card title="Session Details">
                <dl class="space-y-4">
                    <x-kpi label="Teller" :value="$blotter['teller']" :mono="false" />
                    <x-kpi label="Branch" :value="$blotter['branch']" :mono="false" />
                    <x-kpi label="Business date" :value="Format::date($blotter['date'])" />
                    <x-kpi label="Opened at" value="07:45 AM" />
                    <x-kpi label="Receipts issued" value="42" />
                    <x-kpi label="Status" value="Open" :mono="false" />
                </dl>
            </x-card>

            <x-card title="Variance Notes">
                <x-form-field label="Explanation" type="textarea" name="variance_note" rows="4"
                              placeholder="Required when the variance is not zero."
                              help="Reviewed by the branch manager as part of end-of-day approval." />
                <x-btn class="mt-4 w-full" icon="folder-open">Save note</x-btn>
            </x-card>

            <x-card title="Checklist before closing">
                <ul class="space-y-2.5">
                    @foreach([
                        'All collection sheets posted',
                        'All disbursement vouchers signed',
                        'GCash transfers reconciled',
                        'Cash counted by a second person',
                    ] as $index => $item)
                        <li class="flex items-start gap-2.5">
                            <input id="close-check-{{ $index }}" type="checkbox" @checked($index < 3)
                                   class="mt-0.5 h-4 w-4 rounded-[4px] border-neutral-300 text-primary-600">
                            <label for="close-check-{{ $index }}" class="text-[13px] text-neutral-700">{{ $item }}</label>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        </aside>
    </div>

    {{-- ================================================================ close day --}}
    <x-modal name="close-day" title="Close the business day?" icon="lock" tone="warning"
             subtitle="No further transactions can be posted against this date.">
        <dl class="grid grid-cols-2 gap-4 rounded-[12px] border border-neutral-200 bg-neutral-50 p-4">
            <x-kpi label="Expected cash" :value="Format::peso($blotter['expected'])" />
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-neutral-500">Actual count</dt>
                <dd class="mt-1 text-[15px] font-semibold tabular-nums text-neutral-900" x-text="peso(total())"></dd>
            </div>
            <div class="col-span-2 border-t border-neutral-200 pt-3">
                <dt class="text-xs font-medium uppercase tracking-wide text-neutral-500">Variance</dt>
                <dd class="mt-1 text-lg font-semibold tabular-nums"
                    :class="Math.abs(variance()) > 0.005 ? 'text-danger' : 'text-success'"
                    x-text="peso(variance())"></dd>
            </div>
        </dl>

        <div x-show="Math.abs(variance()) > 0.005" x-cloak
             class="mt-4 flex items-start gap-2.5 rounded-[8px] border border-[#F5B5AE] bg-[#FEECEA] p-3.5">
            <x-icon name="alert-triangle" class="mt-0.5 shrink-0 text-danger" />
            <p class="text-[13px] text-danger">
                The drawer does not balance. Closing with a variance requires a branch manager's approval and a written
                explanation, both of which are recorded in the audit log.
            </p>
        </div>

        <x-form-field class="mt-4" label="Approving officer" type="select" name="approver" required
                      :options="['Teresita G. Gonzales — Branch Manager', 'Editha C. Ramirez — Super Admin']" />

        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="primary" icon="lock">Close Day</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
