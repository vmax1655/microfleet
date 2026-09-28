@php
    use App\Support\Format;

    // Capacity-to-pay: amortisation as a share of net monthly income.
    // Policy threshold is 40% — lower is healthier, so the tone is inverted
    // relative to the meter's default "higher is better" reading.
    $capacityTone = match (true) {
        $capacityRatio <= 25 => 'success',
        $capacityRatio <= 40 => 'warning',
        default => 'danger',
    };

    $documents = [
        ['Valid ID (PhilSys)', 'Verified'],
        ['Barangay Clearance', 'Verified'],
        ['Proof of Billing', 'Verified'],
        ['Business Permit', 'Under Review'],
        ['Co-maker Valid ID', 'Submitted'],
    ];
@endphp

<div>
    <x-breadcrumb />

    <x-page-header
        title="Credit Assessment"
        subtitle="Applications that have cleared initial review and need a credit decision.">
        <x-slot:actions>
            <x-btn icon="download">Export queue</x-btn>
            <x-btn variant="primary" icon="eye" @click="$dispatch('open-drawer', 'assessment')">Assess Application</x-btn>
        </x-slot:actions>
    </x-page-header>

    <section aria-label="Assessment summary" class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Awaiting Assessment" :value="count($queue)" :delta="4.5" good-direction="down" icon="clipboard-list" />
        <x-stat-card label="Requested Value" :value="Format::pesoCompact(array_sum(array_column($queue, 'amount')))" :delta="6.2" icon="wallet" />
        <x-stat-card label="Avg. Credit Score" value="668" :delta="1.9" icon="target" />
        <x-stat-card label="Avg. Capacity Ratio" value="31.4%" :delta="2.2" good-direction="down" icon="percent" />
    </section>

    <x-filter-bar search-label="Search queue" search-placeholder="Application ID or member name…">
        <x-select label="Score band" :options="['Excellent (740+)', 'Good (670–739)', 'Fair (580–669)', 'Poor (below 580)']" placeholder="Any band" width="w-44" />
        <x-select label="Capacity ratio" :options="['Within policy (≤40%)', 'Above policy (>40%)']" placeholder="Any ratio" width="w-48" />
        <x-select label="Stage" :options="['Under Review', 'Credit Assessment']" placeholder="All stages" width="w-44" />
    </x-filter-bar>

    <x-card flush>
        @if(count($queue) === 0)
            <x-empty-state
                icon="check-circle"
                heading="No applications awaiting assessment"
                help="Applications move here automatically once initial review is complete."
                action-label="View pipeline"
                :action-href="route('loans.applications')"
                action-icon="columns-3" />
        @else
            <x-data-table sort-key="days" sort-dir="desc" caption="Applications awaiting credit assessment">
                <x-slot:head>
                    <x-th sort="app">Application</x-th>
                    <x-th sort="member">Member</x-th>
                    <x-th sort="product">Product</x-th>
                    <x-th sort="amount" align="right">Requested</x-th>
                    <x-th sort="score" align="right">Credit Score</x-th>
                    <x-th sort="capacity">Capacity to Pay</x-th>
                    <x-th sort="days" align="right">Days Waiting</x-th>
                    <x-th align="right" sr-only>Actions</x-th>
                </x-slot:head>

                @foreach($queue as $i => $row)
                    @php
                        $rowTone = match (true) {
                            $row['capacity_ratio'] <= 25 => 'success',
                            $row['capacity_ratio'] <= 40 => 'warning',
                            default => 'danger',
                        };
                    @endphp
                    <tr data-row data-app="{{ $row['id'] }}" data-member="{{ $row['member'] }}"
                        data-product="{{ $row['product'] }}" data-amount="{{ $row['amount'] }}"
                        data-score="{{ $row['credit_score'] }}" data-capacity="{{ $row['capacity_ratio'] }}"
                        data-days="{{ $row['days_in_stage'] }}"
                        class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">

                        <td data-label="Application" class="px-3 py-2.5 font-medium tabular-nums text-neutral-800">{{ $row['id'] }}</td>

                        <td data-label="Member" class="px-3 py-2.5">
                            <div class="flex items-center gap-2.5">
                                <x-avatar :name="$row['member']" size="sm" />
                                <div class="min-w-0">
                                    <span class="block truncate font-medium text-neutral-800">{{ $row['member'] }}</span>
                                    <span class="block truncate text-xs text-neutral-500">{{ $row['center'] }}</span>
                                </div>
                            </div>
                        </td>

                        <td data-label="Product" class="px-3 py-2.5 text-neutral-700">{{ $row['product'] }}</td>
                        <td data-label="Requested" class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800">{{ Format::peso($row['amount']) }}</td>
                        <td data-label="Credit Score" class="px-3 py-2.5 text-right tabular-nums text-neutral-700">{{ $row['credit_score'] }}</td>
                        <td data-label="Capacity to Pay" class="px-3 py-2.5">
                            <x-meter :value="$row['capacity_ratio']" :tone="$rowTone" size="sm" class="w-28" />
                        </td>
                        <td data-label="Days Waiting" class="px-3 py-2.5 text-right tabular-nums {{ $row['days_in_stage'] > 7 ? 'font-medium text-danger' : 'text-neutral-600' }}">{{ $row['days_in_stage'] }}</td>

                        <td data-label="" class="px-3 py-2.5 text-right">
                            <x-btn size="sm" icon="eye" @click="$dispatch('open-drawer', 'assessment')">Assess</x-btn>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>

            <x-pagination :from="1" :to="count($queue)" :total="count($queue)" :current="1" />
        @endif
    </x-card>

    {{-- ================================================================ assessment drawer --}}
    <x-drawer name="assessment"
              :title="$app['id']"
              :subtitle="$app['member'].' · '.$app['center']"
              width="max-w-3xl">

        <div class="space-y-5">

            {{-- Applicant summary --}}
            <section class="rounded-[12px] border border-neutral-200 bg-neutral-50 p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <x-avatar :name="$app['member']" size="lg" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-neutral-800">{{ $app['member'] }}</p>
                        <p class="truncate text-xs text-neutral-500">{{ $app['member_id'] }} · {{ $member['livelihood'] }}</p>
                    </div>
                    <x-status-badge :status="$app['stage']" />
                    <x-btn size="sm" icon="external-link" :href="route('membership.profile', $app['member_id'])">Profile</x-btn>
                </div>

                <dl class="mt-4 grid grid-cols-2 gap-4 border-t border-neutral-200 pt-4 sm:grid-cols-4">
                    <x-kpi label="Cycles completed" value="3" />
                    <x-kpi label="On-time rate" :value="Format::percent($member['on_time_rate'])" />
                    <x-kpi label="Savings balance" :value="Format::peso($member['savings'])" />
                    <x-kpi label="Member since" :value="Format::date($member['joined'])" />
                </dl>
            </section>

            {{-- Request terms --}}
            <section>
                <h3 class="mb-2.5 text-[13px] font-semibold uppercase tracking-wide text-neutral-500">Requested terms</h3>
                <dl class="grid grid-cols-2 gap-4 rounded-[12px] border border-neutral-200 p-4 sm:grid-cols-4">
                    <x-kpi label="Amount" :value="Format::peso($app['amount'])" />
                    <x-kpi label="Product" :value="$app['product']" :mono="false" />
                    <x-kpi label="Term" :value="$app['term'].' months'" />
                    <x-kpi label="Frequency" :value="$app['frequency']" :mono="false" />
                    <x-kpi label="Interest rate" :value="$app['rate'].'% '.$app['method']" />
                    <x-kpi label="Total interest" :value="Format::peso($totalInterest)" />
                    <x-kpi label="Instalments" :value="$periods.' × '.Format::peso($amortisation)" />
                    <x-kpi label="Monthly amortisation" :value="Format::peso($monthlyAmortisation)" tone="default" />
                </dl>
                <p class="mt-2 text-[13px] text-neutral-600">
                    <span class="font-medium text-neutral-800">Purpose:</span> {{ $app['purpose'] }}
                </p>
            </section>

            {{-- Capacity + score --}}
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-[12px] border border-neutral-200 p-4">
                    <h3 class="text-[13px] font-semibold uppercase tracking-wide text-neutral-500">Capacity to pay</h3>
                    <p class="mt-2 text-3xl font-semibold tabular-nums text-neutral-900">{{ Format::percent($capacityRatio) }}</p>
                    <p class="mt-0.5 text-xs text-neutral-500 tabular-nums">
                        {{ Format::peso($monthlyAmortisation) }} of {{ Format::peso($netIncome) }} net monthly income
                    </p>
                    <x-meter class="mt-3" :value="$capacityRatio" :tone="$capacityTone" :show-value="false" />
                    <p class="mt-2 text-xs {{ $capacityTone === 'danger' ? 'text-danger' : 'text-neutral-500' }}">
                        Policy ceiling is 40%. {{ $capacityRatio <= 40 ? 'Within policy.' : 'Above policy — requires manager override.' }}
                    </p>
                </div>

                <div class="rounded-[12px] border border-neutral-200 p-4">
                    <h3 class="text-[13px] font-semibold uppercase tracking-wide text-neutral-500">Credit score</h3>
                    <x-charts.credit-score-gauge :score="$app['credit_score']" height="h-36" />
                </div>
            </section>

            {{-- Guarantor --}}
            <section>
                <h3 class="mb-2.5 text-[13px] font-semibold uppercase tracking-wide text-neutral-500">Co-maker & collateral</h3>
                <dl class="grid grid-cols-1 gap-4 rounded-[12px] border border-neutral-200 p-4 sm:grid-cols-2">
                    <x-kpi label="Co-maker" :value="$app['comaker']" :mono="false" />
                    <x-kpi label="Relationship" :value="$app['comaker_relation']" :mono="false" />
                    <x-kpi label="Co-maker standing" value="Active · no arrears" :mono="false" />
                    <x-kpi label="Collateral offered" :value="$app['collateral']" :mono="false" />
                </dl>
            </section>

            {{-- Documents --}}
            <section>
                <h3 class="mb-2.5 text-[13px] font-semibold uppercase tracking-wide text-neutral-500">Attached documents</h3>
                <ul class="divide-y divide-neutral-200 rounded-[12px] border border-neutral-200">
                    @foreach($documents as [$docName, $docStatus])
                        <li class="flex items-center gap-3 px-4 py-3">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-[8px] bg-neutral-100 text-neutral-600">
                                <x-icon name="file-text" class="h-4 w-4" />
                            </span>
                            <span class="min-w-0 flex-1 truncate text-[13px] text-neutral-700">{{ $docName }}</span>
                            <x-status-badge :status="$docStatus" />
                            <x-btn size="xs" variant="ghost" icon="eye" aria-label="Preview {{ $docName }}" />
                        </li>
                    @endforeach
                </ul>
            </section>

            {{-- Decision notes --}}
            <x-form-field label="Assessment notes" type="textarea" name="assessment_notes" rows="3"
                          placeholder="Record what supports or contradicts the recommendation."
                          help="Written to the audit log alongside your decision." />
        </div>

        <x-slot:footer>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <x-btn variant="danger-outline" icon="x" @click="$dispatch('open-modal', 'reject-assessment')">Reject</x-btn>
                <div class="flex items-center gap-2">
                    <x-btn icon="send" @click="$dispatch('open-modal', 'request-info')">Request Info</x-btn>
                    <x-btn variant="primary" icon="check" @click="$dispatch('open-modal', 'approve-assessment')">Approve</x-btn>
                </div>
            </div>
        </x-slot:footer>
    </x-drawer>

    {{-- ================================================================ decision modals --}}
    <x-modal name="approve-assessment" title="Approve this application?" tone="success" icon="check-circle"
             :subtitle="$app['id'].' · '.$app['member']">
        <dl class="grid grid-cols-2 gap-4 rounded-[8px] border border-neutral-200 bg-neutral-50 p-3.5">
            <x-kpi label="Approved amount" :value="Format::peso($app['amount'])" />
            <x-kpi label="Term" :value="$app['term'].' months'" />
            <x-kpi label="Monthly amortisation" :value="Format::peso($monthlyAmortisation)" />
            <x-kpi label="Capacity ratio" :value="Format::percent($capacityRatio)" :tone="$capacityTone === 'danger' ? 'danger' : 'default'" />
        </dl>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-form-field label="Approved amount" name="approved_amount" prefix="₱"
                          :value="number_format($app['amount'], 2)" tabular required
                          help="You may approve a lower amount than requested." />
            <x-form-field label="Release method" name="release_method" type="select"
                          :options="['Cash', 'GCash', 'Bank Transfer']" value="GCash" required />
        </div>

        <p class="mt-4 text-[13px] text-neutral-600">
            On approval this application moves to the disbursement queue and the assigned loan officer is notified.
        </p>

        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="success" icon="check">Confirm Approval</x-btn>
        </x-slot:footer>
    </x-modal>

    <x-modal name="request-info" title="Request more information" icon="send" tone="info"
             subtitle="The loan officer is notified and the application stays in this stage.">
        <x-form-field label="What is missing?" type="textarea" name="info_request" rows="3" required
                      placeholder="e.g. Latest 3 months of sales records and an updated business permit." />
        <x-form-field class="mt-4" label="Respond by" name="respond_by" type="date" value="2026-09-16" tabular required />

        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="primary" icon="send">Send Request</x-btn>
        </x-slot:footer>
    </x-modal>

    <x-modal name="reject-assessment" title="Reject this application?" tone="danger" icon="alert-triangle"
             subtitle="This decision is final and is recorded in the audit log.">
        <x-form-field label="Reason" type="select" name="reject_reason" required
                      :options="[
                          'Capacity-to-pay above the 40% policy ceiling',
                          'Credit score below the minimum threshold',
                          'Adverse repayment history',
                          'Incomplete or unverifiable documents',
                          'Co-maker not eligible',
                          'Other',
                      ]" />
        <x-form-field class="mt-4" label="Explanation to the loan officer" type="textarea" name="reject_detail" rows="3" required
                      placeholder="Explain the decision so the officer can advise the member." />

        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="danger" icon="x">Reject Application</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
