@php
    use App\Support\Format;

    $restructure = $requests[0];

    // Old vs new schedule preview for the restructure form.
    $oldSchedule = collect(range(1, 6))->map(fn ($n) => [
        'no' => $n,
        'due' => date('Y-m-d', strtotime('2026-09-15 +'.($n - 1).' months')),
        'amount' => $restructure['old_amort'],
    ])->all();

    $newSchedule = collect(range(1, 6))->map(fn ($n) => [
        'no' => $n,
        'due' => date('Y-m-d', strtotime('2026-10-15 +'.($n - 1).' months')),
        'amount' => $restructure['new_amort'],
    ])->all();
@endphp

<div>
    <x-breadcrumb />

    <x-page-header
        title="Penalties & Restructuring"
        subtitle="Accrued penalties awaiting a decision, and requests to reschedule a struggling loan.">
        <x-slot:actions>
            <x-btn icon="download">Export</x-btn>
            <x-btn variant="primary" icon="plus" @click="$dispatch('open-drawer', 'restructure-form')">New Restructure</x-btn>
        </x-slot:actions>
    </x-page-header>

    <section aria-label="Penalty summary" class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Penalties Accrued" :value="Format::peso($accrued)" :delta="4.8" good-direction="down" icon="alert-triangle" />
        <x-stat-card label="Penalties Waived" :value="Format::peso($waived)" :delta="1.9" good-direction="down" icon="shield" />
        <x-stat-card label="Restructure Requests" :value="count($requests)" :delta="12.5" good-direction="down" icon="refresh-cw" />
        <x-stat-card label="Restructured Portfolio" :value="Format::pesoCompact(2140000)" :delta="6.2" good-direction="down" icon="wallet" />
    </section>

    <x-tabs :tabs="[
        ['key' => 'penalties', 'label' => 'Penalties', 'icon' => 'alert-triangle', 'count' => count($penalties)],
        ['key' => 'restructuring', 'label' => 'Restructuring', 'icon' => 'refresh-cw', 'count' => count($requests)],
    ]">

        {{-- ============================================================ Penalties --}}
        <div x-show="tab === 'penalties'" role="tabpanel">
            <x-filter-bar search-label="Search penalties" search-placeholder="Member name, loan ID, or penalty ID…" date-range>
                <x-select label="Status" :options="['Pending', 'Waived', 'Collected']" placeholder="All statuses" width="w-36" />
                <x-select label="Days late" :options="['1–14 days', '15–30 days', '31–60 days', 'Over 60 days']" placeholder="Any" width="w-36" />
            </x-filter-bar>

            <x-card flush>
                @if(count($penalties) === 0)
                    <x-empty-state
                        icon="shield-check"
                        heading="No penalties accrued"
                        help="Penalties accrue automatically once an instalment passes the product's grace period."
                        action-label="View past due accounts"
                        :action-href="route('collections.par')"
                        action-icon="alert-triangle" />
                @else
                    <x-data-table sort-key="accrued" sort-dir="desc" caption="Accrued penalties">
                        <x-slot:head>
                            <x-th sort="penalty">Penalty ID</x-th>
                            <x-th sort="member">Member</x-th>
                            <x-th sort="loan">Loan ID</x-th>
                            <x-th sort="misseddue">Missed Due Date</x-th>
                            <x-th sort="dayslate" align="right">Days Late</x-th>
                            <x-th sort="base" align="right">Base Amount</x-th>
                            <x-th sort="rate" align="right">Rate</x-th>
                            <x-th sort="accrued" align="right">Penalty Accrued</x-th>
                            <x-th sort="status">Status</x-th>
                            <x-th align="right" sr-only>Actions</x-th>
                        </x-slot:head>

                        @foreach($penalties as $i => $p)
                            <tr data-row data-penalty="{{ $p['id'] }}" data-member="{{ $p['member'] }}"
                                data-loan="{{ $p['loan_id'] }}" data-misseddue="{{ $p['missed_due'] }}"
                                data-dayslate="{{ $p['days_late'] }}" data-base="{{ $p['base_amount'] }}"
                                data-rate="{{ $p['penalty_rate'] }}" data-accrued="{{ $p['accrued'] }}"
                                data-status="{{ $p['status'] }}"
                                class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">

                                <td data-label="Penalty ID" class="px-3 py-2.5 tabular-nums text-neutral-700">{{ $p['id'] }}</td>

                                <td data-label="Member" class="px-3 py-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <x-avatar :name="$p['member']" size="sm" />
                                        <div class="min-w-0">
                                            <span class="block truncate font-medium text-neutral-800">{{ $p['member'] }}</span>
                                            <span class="block truncate text-xs text-neutral-500">{{ $p['center'] }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td data-label="Loan ID" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ $p['loan_id'] }}</td>
                                <td data-label="Missed Due Date" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ Format::date($p['missed_due']) }}</td>
                                <td data-label="Days Late" class="px-3 py-2.5 text-right font-medium tabular-nums text-danger">{{ $p['days_late'] }}</td>
                                <td data-label="Base Amount" class="px-3 py-2.5 text-right tabular-nums text-neutral-700">{{ Format::peso($p['base_amount']) }}</td>
                                <td data-label="Rate" class="px-3 py-2.5 text-right tabular-nums text-neutral-600">{{ $p['penalty_rate'] }}%</td>
                                <td data-label="Penalty Accrued" class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">{{ Format::peso($p['accrued']) }}</td>
                                <td data-label="Status" class="px-3 py-2.5"><x-status-badge :status="$p['status']" /></td>

                                <td data-label="" class="px-3 py-2.5 text-right">
                                    @if($p['status'] === 'Pending')
                                        <x-btn size="sm" icon="shield" @click="$dispatch('open-modal', 'waive-penalty')">Waive</x-btn>
                                    @else
                                        <span class="text-xs text-neutral-500">Waived 04 Sep 2026</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach

                        <x-slot:foot>
                            <tr>
                                <td data-label="" class="px-3 py-2.5 text-[13px] font-semibold text-neutral-700" colspan="7">Total accrued</td>
                                <td data-label="Penalty Accrued" class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">
                                    {{ Format::peso($accrued + $waived) }}
                                </td>
                                <td data-label="" class="px-3 py-2.5" colspan="2"></td>
                            </tr>
                        </x-slot:foot>
                    </x-data-table>

                    <x-pagination :from="1" :to="count($penalties)" :total="88" :current="1" :per-page="14" />
                @endif
            </x-card>
        </div>

        {{-- ============================================================ Restructuring --}}
        <div x-show="tab === 'restructuring'" x-cloak role="tabpanel">
            <x-filter-bar search-label="Search requests" search-placeholder="Member name, loan ID, or request ID…">
                <x-select label="Status" :options="['Submitted', 'Under Review', 'Approved', 'Rejected']" placeholder="All statuses" width="w-40" />
                <x-select label="Reason" :options="['Calamity', 'Illness', 'Loss of income', 'Crop failure', 'Other']" placeholder="Any reason" width="w-40" />
            </x-filter-bar>

            <x-card flush>
                @if(count($requests) === 0)
                    <x-empty-state
                        icon="refresh-cw"
                        heading="No restructuring requests"
                        help="Loan officers raise a request when a member's circumstances change and the original schedule is no longer viable."
                        action-label="New Restructure" />
                @else
                    <x-data-table sort-key="requested" sort-dir="desc" caption="Loan restructuring requests">
                        <x-slot:head>
                            <x-th sort="request">Request ID</x-th>
                            <x-th sort="member">Member</x-th>
                            <x-th sort="loan">Loan ID</x-th>
                            <x-th sort="outstanding" align="right">Outstanding</x-th>
                            <x-th sort="oldamort" align="right">Current Amort.</x-th>
                            <x-th sort="newamort" align="right">Proposed Amort.</x-th>
                            <x-th sort="reason">Reason</x-th>
                            <x-th sort="requested">Requested</x-th>
                            <x-th sort="status">Status</x-th>
                            <x-th align="right" sr-only>Actions</x-th>
                        </x-slot:head>

                        @foreach($requests as $i => $r)
                            <tr data-row data-request="{{ $r['id'] }}" data-member="{{ $r['member'] }}"
                                data-loan="{{ $r['loan_id'] }}" data-outstanding="{{ $r['outstanding'] }}"
                                data-oldamort="{{ $r['old_amort'] }}" data-newamort="{{ $r['new_amort'] }}"
                                data-reason="{{ $r['reason'] }}" data-requested="{{ $r['requested_on'] }}"
                                data-status="{{ $r['status'] }}"
                                class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">

                                <td data-label="Request ID" class="px-3 py-2.5 tabular-nums text-neutral-700">{{ $r['id'] }}</td>

                                <td data-label="Member" class="px-3 py-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <x-avatar :name="$r['member']" size="sm" />
                                        <div class="min-w-0">
                                            <span class="block truncate font-medium text-neutral-800">{{ $r['member'] }}</span>
                                            <span class="block truncate text-xs text-neutral-500">{{ $r['center'] }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td data-label="Loan ID" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ $r['loan_id'] }}</td>
                                <td data-label="Outstanding" class="px-3 py-2.5 text-right tabular-nums text-neutral-800">{{ Format::peso($r['outstanding']) }}</td>
                                <td data-label="Current Amort." class="px-3 py-2.5 text-right tabular-nums text-neutral-600">{{ Format::peso($r['old_amort']) }}</td>
                                <td data-label="Proposed Amort." class="px-3 py-2.5 text-right font-medium tabular-nums text-primary-700">{{ Format::peso($r['new_amort']) }}</td>
                                <td data-label="Reason" class="px-3 py-2.5 text-neutral-600">{{ $r['reason'] }}</td>
                                <td data-label="Requested" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ Format::date($r['requested_on']) }}</td>
                                <td data-label="Status" class="px-3 py-2.5"><x-status-badge :status="$r['status']" /></td>

                                <td data-label="" class="px-3 py-2.5 text-right">
                                    <x-btn size="sm" icon="eye" @click="$dispatch('open-drawer', 'restructure-form')">Review</x-btn>
                                </td>
                            </tr>
                        @endforeach
                    </x-data-table>
                @endif
            </x-card>
        </div>
    </x-tabs>

    {{-- ================================================================ waive modal --}}
    <x-modal name="waive-penalty" title="Waive this penalty?" icon="shield" tone="warning"
             subtitle="The penalty is removed from the loan ledger and recorded as a concession.">
        <dl class="grid grid-cols-2 gap-4 rounded-[12px] border border-neutral-200 bg-neutral-50 p-4">
            <x-kpi label="Penalty" :value="$penalties[0]['id']" />
            <x-kpi label="Amount to waive" :value="Format::peso($penalties[0]['accrued'])" />
            <x-kpi label="Member" :value="$penalties[0]['member']" :mono="false" />
            <x-kpi label="Days late" :value="$penalties[0]['days_late']" />
        </dl>

        <x-form-field class="mt-4" label="Reason for waiver" type="select" name="waive_reason" required
                      :options="[
                          'Calamity declared in the service area',
                          'Documented medical emergency',
                          'System or posting error',
                          'Goodwill — long-standing member in good standing',
                          'Other',
                      ]" />

        <x-form-field class="mt-4" label="Supporting detail" type="textarea" name="waive_detail" rows="2" required
                      placeholder="Reference the supporting document or resolution number." />

        <x-form-field class="mt-4" label="Approving officer" type="select" name="approver" required
                      :options="['Teresita G. Gonzales — Branch Manager', 'Editha C. Ramirez — Super Admin', 'Benigno L. Ocampo — Branch Manager']"
                      help="Waivers above ₱500.00 require branch manager approval." />

        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="primary" icon="check">Waive Penalty</x-btn>
        </x-slot:footer>
    </x-modal>

    {{-- ================================================================ restructure form --}}
    <x-drawer name="restructure-form"
              title="Restructure loan"
              :subtitle="$restructure['id'].' · '.$restructure['member'].' · '.$restructure['loan_id']"
              width="max-w-4xl">

        <div class="space-y-5">
            <dl class="grid grid-cols-2 gap-4 rounded-[12px] border border-neutral-200 bg-neutral-50 p-4 sm:grid-cols-4">
                <x-kpi label="Outstanding" :value="Format::peso($restructure['outstanding'])" />
                <x-kpi label="Current term" :value="$restructure['old_term'].' months'" />
                <x-kpi label="Days past due" value="63" tone="danger" />
                <x-kpi label="Requested on" :value="Format::date($restructure['requested_on'])" />
            </dl>

            <div class="rounded-[12px] border border-neutral-200 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-neutral-500">Reason given</p>
                <p class="mt-1.5 text-[13px] text-neutral-700">{{ $restructure['reason'] }}</p>
            </div>

            {{-- Proposed terms --}}
            <div>
                <h3 class="mb-3 text-[13px] font-semibold uppercase tracking-wide text-neutral-500">Proposed terms</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-form-field label="New term" name="new_term" suffix="mo" :value="$restructure['new_term']" required tabular />
                    <x-form-field label="New amortisation" name="new_amort" prefix="₱"
                                  :value="number_format($restructure['new_amort'], 2)" required tabular />
                    <x-form-field label="First due date" name="first_due" type="date" value="2026-10-15" required tabular />
                    <x-form-field label="Capitalise accrued interest" type="select" name="capitalise"
                                  :options="['Yes', 'No']" value="Yes" />
                    <x-form-field label="Waive accrued penalties" type="select" name="waive_penalties"
                                  :options="['Yes', 'No', 'Partial']" value="Partial" />
                    <x-form-field label="New interest rate" name="new_rate" suffix="%" value="2.0" tabular />
                </div>
            </div>

            {{-- Old vs new side by side --}}
            <div>
                <h3 class="mb-3 text-[13px] font-semibold uppercase tracking-wide text-neutral-500">Schedule comparison</h3>
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

                    <div class="overflow-hidden rounded-[12px] border border-neutral-200">
                        <header class="border-b border-neutral-200 bg-neutral-100 px-4 py-2.5">
                            <h4 class="text-[13px] font-semibold text-neutral-700">Current schedule</h4>
                            <p class="text-xs tabular-nums text-neutral-500">
                                {{ $restructure['old_term'] }} months · {{ Format::peso($restructure['old_amort']) }} monthly
                            </p>
                        </header>
                        <table class="w-full text-sm">
                            <caption class="sr-only">Current repayment schedule</caption>
                            <thead class="bg-neutral-50">
                                <tr>
                                    <th scope="col" class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-neutral-600">#</th>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-neutral-600">Due Date</th>
                                    <th scope="col" class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-neutral-600">Amount Due</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-200">
                                @foreach($oldSchedule as $row)
                                    <tr class="{{ $loop->index % 2 ? 'bg-neutral-50' : '' }}">
                                        <td class="px-3 py-2 text-right tabular-nums text-neutral-500">{{ $row['no'] }}</td>
                                        <td class="px-3 py-2 tabular-nums text-neutral-700">{{ Format::date($row['due']) }}</td>
                                        <td class="px-3 py-2 text-right font-medium tabular-nums text-neutral-800">{{ Format::peso($row['amount']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="overflow-hidden rounded-[12px] border border-primary-300">
                        <header class="border-b border-primary-200 bg-primary-50 px-4 py-2.5">
                            <h4 class="text-[13px] font-semibold text-primary-800">Proposed schedule</h4>
                            <p class="text-xs tabular-nums text-primary-800/80">
                                {{ $restructure['new_term'] }} months · {{ Format::peso($restructure['new_amort']) }} monthly
                            </p>
                        </header>
                        <table class="w-full text-sm">
                            <caption class="sr-only">Proposed repayment schedule after restructuring</caption>
                            <thead class="bg-neutral-50">
                                <tr>
                                    <th scope="col" class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-neutral-600">#</th>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-neutral-600">Due Date</th>
                                    <th scope="col" class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-neutral-600">Amount Due</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-200">
                                @foreach($newSchedule as $row)
                                    <tr class="{{ $loop->index % 2 ? 'bg-primary-50/50' : '' }}">
                                        <td class="px-3 py-2 text-right tabular-nums text-neutral-500">{{ $row['no'] }}</td>
                                        <td class="px-3 py-2 tabular-nums text-neutral-700">{{ Format::date($row['due']) }}</td>
                                        <td class="px-3 py-2 text-right font-medium tabular-nums text-primary-800">{{ Format::peso($row['amount']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <p class="mt-3 text-[13px] text-neutral-600">
                    Monthly burden drops by
                    <span class="font-semibold tabular-nums text-success">{{ Format::peso($restructure['old_amort'] - $restructure['new_amort']) }}</span>,
                    with the term extended from {{ $restructure['old_term'] }} to {{ $restructure['new_term'] }} months.
                </p>
            </div>

            <x-form-field label="Recommendation" type="textarea" name="recommendation" rows="3"
                          placeholder="State why restructuring is preferable to write-off for this account." />
        </div>

        <x-slot:footer>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <x-btn variant="danger-outline" icon="x">Reject request</x-btn>
                <div class="flex items-center gap-2">
                    <x-btn @click="$dispatch('close-drawer')">Cancel</x-btn>
                    <x-btn variant="primary" icon="check">Approve Restructure</x-btn>
                </div>
            </div>
        </x-slot:footer>
    </x-drawer>
</div>
