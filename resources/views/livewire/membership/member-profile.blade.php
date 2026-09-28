@php
    use App\Support\Format;

    $trail = [
        ['label' => 'Membership', 'route' => 'membership.directory'],
        ['label' => 'Member Directory', 'route' => 'membership.directory'],
        ['label' => $member['name']],
    ];

    $notes = [
        ['author' => 'Grace M. Villamor', 'date' => '2026-09-02', 'body' => 'Visited the store during center meeting. Stock levels look healthy; borrower requested a top-up loan after the current cycle.'],
        ['author' => 'Teresita G. Gonzales', 'date' => '2026-07-18', 'body' => 'Approved reduction of processing fee to 2.5% as a loyalty concession — 4th cycle borrower with no missed payment.'],
        ['author' => 'Arnel P. Bacani', 'date' => '2026-04-05', 'body' => 'Updated contact number after the member changed SIM. Old number no longer reachable.'],
    ];
@endphp

<div>
    <x-breadcrumb :trail="$trail" />

    {{-- ---------------------------------------------------------------- header card --}}
    <x-card class="mb-5">
        <div class="flex flex-wrap items-start gap-5">
            <x-avatar :name="$member['name']" size="xl" />

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-semibold tracking-tight text-neutral-800">{{ $member['name'] }}</h1>
                    <x-status-badge :status="$member['status']" />
                    <x-status-badge :status="$member['kyc']">KYC {{ $member['kyc'] }}</x-status-badge>
                </div>

                <dl class="mt-3 grid grid-cols-2 gap-x-6 gap-y-2.5 sm:grid-cols-3 lg:grid-cols-5">
                    <x-kpi label="Member ID" :value="$member['id']" />
                    <x-kpi label="Branch" :value="$member['branch']" :mono="false" />
                    <x-kpi label="Center" :value="$member['center']" :mono="false" />
                    <x-kpi label="Loan Officer" :value="$member['officer']" :mono="false" />
                    <x-kpi label="Joined" :value="Format::date($member['joined'])" />
                </dl>
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <x-btn icon="send">Send SMS</x-btn>
                <x-btn icon="pencil">Edit</x-btn>
                <x-btn variant="primary" icon="plus" :href="route('loans.applications')">New Loan Application</x-btn>
            </div>
        </div>
    </x-card>

    {{-- ---------------------------------------------------------------- quick stats --}}
    <section aria-label="Member summary" class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Total Borrowed" :value="Format::peso($member['total_borrowed'])" icon="file-text"
                     hint="Across all completed and active cycles" />
        <x-stat-card label="Outstanding" :value="Format::peso($member['outstanding'])" icon="wallet"
                     hint="{{ $member['active_loans'] }} active loan(s)" />
        <x-stat-card label="Savings Balance" :value="Format::peso($member['savings'])" icon="piggy-bank"
                     hint="Regular savings + share capital" />

        <x-card>
            <h3 class="text-[13px] font-medium text-neutral-500">On-time Payment Rate</h3>
            <p class="mt-2 text-[26px] font-semibold leading-tight tabular-nums text-neutral-900">
                {{ Format::percent($member['on_time_rate']) }}
            </p>
            <x-meter class="mt-3" :value="$member['on_time_rate']" :show-value="false"
                     caption="Based on all scheduled instalments to date" />
        </x-card>
    </section>

    {{-- ---------------------------------------------------------------- tabs --}}
    <x-tabs :tabs="[
        ['key' => 'overview', 'label' => 'Overview', 'icon' => 'user'],
        ['key' => 'loans', 'label' => 'Loans', 'icon' => 'file-text', 'count' => count($loans)],
        ['key' => 'savings', 'label' => 'Savings', 'icon' => 'piggy-bank', 'count' => count($savings)],
        ['key' => 'history', 'label' => 'Repayment History', 'icon' => 'history'],
        ['key' => 'kyc', 'label' => 'KYC Documents', 'icon' => 'shield-check', 'count' => count($documents)],
        ['key' => 'notes', 'label' => 'Notes', 'icon' => 'clipboard-list', 'count' => count($notes)],
    ]">

        {{-- ============================================================ Overview --}}
        <div x-show="tab === 'overview'" role="tabpanel" class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <x-card class="lg:col-span-2" title="Personal Information">
                <dl class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2">
                    <x-kpi label="Full name" :value="$member['name']" :mono="false" />
                    <x-kpi label="Sex" :value="$member['sex'] === 'F' ? 'Female' : 'Male'" :mono="false" />
                    <x-kpi label="Date of birth" :value="Format::date($member['birthdate'])" />
                    <x-kpi label="Mobile number" :value="$member['contact']" />
                    <x-kpi label="Email" :value="$member['email']" :mono="false" />
                    <x-kpi label="Household size" :value="$member['household_size'].' members'" />
                    <div class="sm:col-span-2">
                        <x-kpi label="Home address" :value="$member['address']" :mono="false" />
                    </div>
                </dl>

                <hr class="my-5 border-neutral-200">

                <h3 class="mb-4 text-[13px] font-semibold uppercase tracking-wide text-neutral-500">Livelihood & Income</h3>
                <dl class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2">
                    <x-kpi label="Primary livelihood" :value="$member['livelihood']" :mono="false" />
                    <x-kpi label="Declared monthly income" :value="Format::peso($member['monthly_income'])" />
                    <x-kpi label="Years in business" value="6 years" />
                    <x-kpi label="Business location" value="Public market stall" :mono="false" />
                </dl>
            </x-card>

            <div class="space-y-5">
                <x-card title="Membership Standing">
                    <dl class="space-y-4">
                        <x-kpi label="Share capital" :value="Format::peso($member['shares'] * 100)" :hint="$member['shares'].' shares at ₱100.00 par'" />
                        <x-kpi label="Loan cycles completed" value="4" />
                        <x-kpi label="Longest delay recorded" value="6 days" />
                        <x-kpi label="Attendance rate" value="94.0%" hint="Center meetings, last 12 months" />
                    </dl>
                </x-card>

                <x-card title="Assigned Team">
                    <ul class="space-y-3.5">
                        <li class="flex items-center gap-3">
                            <x-avatar :name="$member['officer']" size="sm" tone="accent" />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-neutral-800">{{ $member['officer'] }}</p>
                                <p class="text-xs text-neutral-500">Loan Officer</p>
                            </div>
                        </li>
                        <li class="flex items-center gap-3">
                            <x-avatar name="Teresita G. Gonzales" size="sm" />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-neutral-800">Teresita G. Gonzales</p>
                                <p class="text-xs text-neutral-500">Branch Manager</p>
                            </div>
                        </li>
                    </ul>
                </x-card>
            </div>
        </div>

        {{-- ============================================================ Loans --}}
        <div x-show="tab === 'loans'" x-cloak role="tabpanel">
            <x-card flush title="Loan Accounts" subtitle="Every loan taken by this member, newest first">
                <x-data-table sort-key="disbursed" sort-dir="desc" caption="Loan accounts for this member">
                    <x-slot:head>
                        <x-th sort="loan">Loan ID</x-th>
                        <x-th sort="product">Product</x-th>
                        <x-th sort="principal" align="right">Principal</x-th>
                        <x-th sort="rate" align="right">Rate</x-th>
                        <x-th sort="term" align="right">Term</x-th>
                        <x-th sort="disbursed">Disbursed</x-th>
                        <x-th sort="outstanding" align="right">Outstanding</x-th>
                        <x-th sort="progress">Progress</x-th>
                        <x-th sort="status">Status</x-th>
                    </x-slot:head>

                    @foreach($loans as $i => $loan)
                        <tr data-row
                            data-loan="{{ $loan['id'] }}"
                            data-product="{{ $loan['product'] }}"
                            data-principal="{{ $loan['principal'] }}"
                            data-rate="{{ $loan['rate'] }}"
                            data-term="{{ $loan['term'] }}"
                            data-disbursed="{{ $loan['disbursed_on'] }}"
                            data-outstanding="{{ $loan['outstanding'] }}"
                            data-progress="{{ $loan['progress'] }}"
                            data-status="{{ $loan['status'] }}"
                            class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">

                            <td data-label="Loan ID" class="px-3 py-2.5">
                                <a href="{{ route('loans.accounts.show', $loan['id']) }}"
                                   class="font-medium tabular-nums text-primary-700 hover:underline">{{ $loan['id'] }}</a>
                            </td>
                            <td data-label="Product" class="px-3 py-2.5 text-neutral-700">{{ $loan['product'] }}</td>
                            <td data-label="Principal" class="px-3 py-2.5 text-right tabular-nums text-neutral-800">{{ Format::peso($loan['principal']) }}</td>
                            <td data-label="Rate" class="px-3 py-2.5 text-right tabular-nums text-neutral-600">{{ $loan['rate'] }}% {{ $loan['method'] }}</td>
                            <td data-label="Term" class="px-3 py-2.5 text-right tabular-nums text-neutral-600">{{ $loan['term'] }} mo</td>
                            <td data-label="Disbursed" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ Format::date($loan['disbursed_on']) }}</td>
                            <td data-label="Outstanding" class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800">{{ Format::peso($loan['outstanding']) }}</td>
                            <td data-label="Progress" class="px-3 py-2.5">
                                <x-meter :value="$loan['progress']" tone="primary" size="sm" class="w-28" />
                            </td>
                            <td data-label="Status" class="px-3 py-2.5"><x-status-badge :status="$loan['status']" /></td>
                        </tr>
                    @endforeach
                </x-data-table>
            </x-card>
        </div>

        {{-- ============================================================ Savings --}}
        <div x-show="tab === 'savings'" x-cloak role="tabpanel" class="space-y-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($savings as $acct)
                    <x-card>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-[13px] font-medium text-neutral-500">{{ $acct['product'] }}</p>
                                <p class="mt-1 text-xs tabular-nums text-neutral-500">{{ $acct['account_no'] }}</p>
                            </div>
                            <x-status-badge :status="$acct['status']" />
                        </div>
                        <p class="mt-3 text-2xl font-semibold tabular-nums text-neutral-900">{{ Format::peso($acct['balance']) }}</p>
                        <p class="mt-1 text-xs text-neutral-500 tabular-nums">
                            {{ $acct['rate'] }}% p.a. · Last activity {{ Format::date($acct['last_txn']) }}
                        </p>
                        <div class="mt-4">
                            <x-btn size="sm" icon="eye" :href="route('savings.accounts.show', $acct['account_no'])">View ledger</x-btn>
                        </div>
                    </x-card>
                @endforeach

                <x-card class="border-dashed">
                    <x-empty-state compact icon="piggy-bank" heading="Open another account"
                                   help="Time deposits and Christmas savings can be opened alongside regular savings."
                                   action-label="Open account" :action-href="route('savings.accounts')" />
                </x-card>
            </div>

            <x-card flush title="Recent Savings Transactions">
                <x-data-table sort-key="date" sort-dir="desc" caption="Recent savings transactions">
                    <x-slot:head>
                        <x-th sort="date">Date</x-th>
                        <x-th sort="or">OR Number</x-th>
                        <x-th sort="type">Type</x-th>
                        <x-th>Description</x-th>
                        <x-th sort="debit" align="right">Debit</x-th>
                        <x-th sort="credit" align="right">Credit</x-th>
                        <x-th sort="balance" align="right">Balance</x-th>
                    </x-slot:head>

                    @foreach($ledger as $i => $t)
                        <tr data-row data-date="{{ $t['date'] }}" data-or="{{ $t['or_no'] }}" data-type="{{ $t['type'] }}"
                            data-debit="{{ $t['debit'] }}" data-credit="{{ $t['credit'] }}" data-balance="{{ $t['balance'] }}"
                            class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">
                            <td data-label="Date" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ Format::date($t['date']) }}</td>
                            <td data-label="OR Number" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ $t['or_no'] }}</td>
                            <td data-label="Type" class="px-3 py-2.5">
                                <x-status-badge :status="$t['type'] === 'Withdrawal' ? 'Pending' : 'Active'" :tone="$t['type'] === 'Withdrawal' ? 'warning' : ($t['type'] === 'Interest' ? 'info' : 'success')">{{ $t['type'] }}</x-status-badge>
                            </td>
                            <td data-label="Description" class="px-3 py-2.5 text-neutral-600">{{ $t['description'] }}</td>
                            <td data-label="Debit" class="px-3 py-2.5 text-right tabular-nums text-neutral-700">{{ $t['debit'] ? Format::peso($t['debit']) : '—' }}</td>
                            <td data-label="Credit" class="px-3 py-2.5 text-right tabular-nums text-neutral-700">{{ $t['credit'] ? Format::peso($t['credit']) : '—' }}</td>
                            <td data-label="Balance" class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800">{{ Format::peso($t['balance']) }}</td>
                        </tr>
                    @endforeach
                </x-data-table>
            </x-card>
        </div>

        {{-- ============================================================ Repayment history --}}
        <div x-show="tab === 'history'" x-cloak role="tabpanel" class="space-y-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-stat-card label="On-time Payments" value="46 of 52" :delta="2.2" icon="check-circle" />
                <x-stat-card label="Late Payments" value="6" :delta="1.0" good-direction="down" icon="clock" />
                <x-stat-card label="Total Paid to Date" :value="Format::peso(148320)" :delta="8.4" icon="banknote" />
            </div>

            <x-card flush title="Repayment History" subtitle="All receipts issued to this member">
                <x-data-table sort-key="date" sort-dir="desc" caption="Repayment history">
                    <x-slot:head>
                        <x-th sort="or">OR Number</x-th>
                        <x-th sort="date">Date</x-th>
                        <x-th sort="loan">Loan ID</x-th>
                        <x-th sort="amount" align="right">Amount</x-th>
                        <x-th sort="method">Method</x-th>
                        <x-th sort="received">Received By</x-th>
                        <x-th sort="status">Status</x-th>
                    </x-slot:head>

                    @foreach($repayments as $i => $r)
                        <tr data-row data-or="{{ $r['or_no'] }}" data-date="{{ $r['date'] }}" data-loan="{{ $r['loan_id'] }}"
                            data-amount="{{ $r['amount'] }}" data-method="{{ $r['method'] }}" data-received="{{ $r['received_by'] }}"
                            data-status="{{ $r['status'] }}"
                            class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">
                            <td data-label="OR Number" class="px-3 py-2.5 tabular-nums text-neutral-700">{{ $r['or_no'] }}</td>
                            <td data-label="Date" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ Format::date($r['date']) }}</td>
                            <td data-label="Loan ID" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ $r['loan_id'] }}</td>
                            <td data-label="Amount" class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800">{{ Format::peso($r['amount']) }}</td>
                            <td data-label="Method" class="px-3 py-2.5 text-neutral-600">{{ $r['method'] }}</td>
                            <td data-label="Received By" class="px-3 py-2.5 text-neutral-600">{{ $r['received_by'] }}</td>
                            <td data-label="Status" class="px-3 py-2.5"><x-status-badge :status="$r['status']" /></td>
                        </tr>
                    @endforeach
                </x-data-table>
            </x-card>
        </div>

        {{-- ============================================================ KYC documents --}}
        <div x-show="tab === 'kyc'" x-cloak role="tabpanel">
            <x-card title="KYC Documents" subtitle="Identity and eligibility requirements on file">
                <x-slot:actions>
                    <x-btn size="sm" icon="upload">Upload document</x-btn>
                </x-slot:actions>

                <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($documents as $doc)
                        <li class="rounded-[12px] border border-neutral-200 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-[8px] bg-neutral-100 text-neutral-600">
                                    <x-icon name="file-text" />
                                </span>
                                <x-status-badge :status="$doc['status']" />
                            </div>
                            <p class="mt-3 text-sm font-medium text-neutral-800">{{ $doc['type'] }}</p>
                            <p class="mt-0.5 truncate text-xs text-neutral-500">{{ $doc['file'] }} · {{ $doc['size'] }}</p>
                            <p class="mt-2 text-xs tabular-nums text-neutral-500">Uploaded {{ Format::date($doc['uploaded']) }}</p>
                            @if($doc['note'])
                                <p class="mt-2 rounded-[8px] bg-[#FEECEA] px-2.5 py-1.5 text-xs text-danger">{{ $doc['note'] }}</p>
                            @endif
                            <div class="mt-3 flex gap-2">
                                <x-btn size="xs" icon="eye" :href="route('membership.kyc')">Review</x-btn>
                                <x-btn size="xs" icon="download">Download</x-btn>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        </div>

        {{-- ============================================================ Notes --}}
        <div x-show="tab === 'notes'" x-cloak role="tabpanel" class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <x-card class="lg:col-span-2" title="Officer Notes" subtitle="Visible to branch staff only">
                <ol class="space-y-5">
                    @foreach($notes as $note)
                        <li class="flex gap-3">
                            <x-avatar :name="$note['author']" size="sm" />
                            <div class="min-w-0 flex-1 rounded-[12px] border border-neutral-200 bg-neutral-50 p-3.5">
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <p class="text-[13px] font-medium text-neutral-800">{{ $note['author'] }}</p>
                                    <p class="text-xs tabular-nums text-neutral-500">{{ Format::date($note['date']) }}</p>
                                </div>
                                <p class="mt-1.5 text-[13px] leading-relaxed text-neutral-600">{{ $note['body'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </x-card>

            <x-card title="Add a note">
                <form class="space-y-4" onsubmit="return false;">
                    <x-form-field label="Note" type="textarea" name="note" rows="5"
                                  placeholder="What happened during the visit or call?" required
                                  help="Notes are appended to the member record and cannot be edited afterwards." />
                    <x-form-field label="Visibility" type="select" name="visibility"
                                  :options="['Branch staff', 'Loan officers only', 'Management only']" />
                    <x-btn variant="primary" icon="plus" class="w-full">Add note</x-btn>
                </form>
            </x-card>
        </div>
    </x-tabs>
</div>
