@php use App\Support\Format; @endphp

<div>
    <x-breadcrumb />

    <x-page-header
        title="Branches & Settings"
        subtitle="Institution profile, branch network, fiscal calendar, and system policies.">
        <x-slot:actions>
            <x-btn icon="download">Export settings</x-btn>
            <x-btn variant="primary" icon="check">Save Changes</x-btn>
        </x-slot:actions>
    </x-page-header>

    <x-tabs :tabs="[
        ['key' => 'institution', 'label' => 'Institution Profile', 'icon' => 'building-2'],
        ['key' => 'branches', 'label' => 'Branches', 'icon' => 'map-pin', 'count' => count($branches)],
        ['key' => 'fiscal', 'label' => 'Fiscal Year', 'icon' => 'calendar'],
        ['key' => 'notifications', 'label' => 'Notifications', 'icon' => 'bell'],
        ['key' => 'security', 'label' => 'Security', 'icon' => 'shield'],
    ]">

        {{-- ============================================================ Institution --}}
        <div x-show="tab === 'institution'" role="tabpanel" class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <x-card class="lg:col-span-2" title="Institution Profile" subtitle="Appears on receipts, statements, and exported reports">
                <form class="grid grid-cols-1 gap-5 sm:grid-cols-2" onsubmit="return false;">
                    <x-form-field class="sm:col-span-2" label="Registered name" name="institution_name"
                                  value="Ledger Microfinance Cooperative" required />
                    <x-form-field label="Trade name" name="trade_name" value="Ledger" />
                    <x-form-field label="CDA registration no." name="cda_no" value="9520-BUL-2016-0114" required tabular />
                    <x-form-field label="TIN" name="tin" value="008-472-115-000" required tabular />
                    <x-form-field label="BSP licence type" type="select" name="licence"
                                  :options="['Microfinance-oriented cooperative', 'Multi-purpose cooperative', 'Credit cooperative']"
                                  value="Microfinance-oriented cooperative" />
                    <x-form-field class="sm:col-span-2" label="Head office address" name="ho_address"
                                  value="2F Sto. Niño Bldg., Paseo del Congreso, Brgy. Catmon, Malolos, Bulacan 3000" required />
                    <x-form-field label="Contact number" name="contact" value="(044) 662 1180" tabular />
                    <x-form-field label="Email address" type="email" name="email" value="info@ledger.coop.ph" />
                    <x-form-field label="Base currency" type="select" name="currency" :options="['PHP — Philippine Peso']" value="PHP — Philippine Peso" disabled />
                    <x-form-field label="Default share par value" name="par_value" prefix="₱" value="100.00" tabular required />
                </form>
            </x-card>

            <x-card title="Branding">
                <div class="grid h-32 w-full place-items-center rounded-[12px] border-2 border-dashed border-neutral-300 bg-neutral-50">
                    <div class="text-center">
                        <span class="mx-auto grid h-10 w-10 place-items-center rounded-[8px] bg-primary-600 text-white">
                            <x-icon name="hand-coins" class="h-5 w-5" />
                        </span>
                        <p class="mt-2 text-[13px] font-medium text-neutral-700">ledger-logo.svg</p>
                        <p class="text-xs text-neutral-500">240 × 240 · 18 KB</p>
                    </div>
                </div>
                <div class="mt-3 flex gap-2">
                    <x-btn size="sm" icon="upload" class="flex-1">Replace</x-btn>
                    <x-btn size="sm" variant="ghost" icon="trash-2" aria-label="Remove logo" />
                </div>

                <dl class="mt-5 space-y-4 border-t border-neutral-200 pt-4">
                    <x-kpi label="Total branches" :value="count($branches)" />
                    <x-kpi label="Total members" :value="number_format(array_sum(array_column($branches, 'members')))" />
                    <x-kpi label="System timezone" value="Asia/Manila (UTC+8)" :mono="false" />
                </dl>
            </x-card>
        </div>

        {{-- ============================================================ Branches --}}
        <div x-show="tab === 'branches'" x-cloak role="tabpanel" class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <x-card class="lg:col-span-2" flush title="Branch Network" subtitle="Every operating branch and its manager">
                @if(count($branches) === 0)
                    <x-empty-state
                        icon="map-pin"
                        heading="No branches configured"
                        help="Add at least one branch before members and loans can be recorded."
                        action-label="Add branch" />
                @else
                    <x-data-table sort-key="portfolio" sort-dir="desc" caption="Branch network">
                        <x-slot:head>
                            <x-th sort="code">Code</x-th>
                            <x-th sort="branch">Branch</x-th>
                            <x-th sort="manager">Manager</x-th>
                            <x-th sort="opened">Opened</x-th>
                            <x-th sort="members" align="right">Members</x-th>
                            <x-th sort="portfolio" align="right">Portfolio</x-th>
                            <x-th align="right" sr-only>Actions</x-th>
                        </x-slot:head>

                        @foreach($branches as $i => $b)
                            <tr data-row data-code="{{ $b['id'] }}" data-branch="{{ $b['name'] }}"
                                data-manager="{{ $b['manager'] }}" data-opened="{{ $b['opened'] }}"
                                data-members="{{ $b['members'] }}" data-portfolio="{{ $b['portfolio'] }}"
                                class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">
                                <td data-label="Code" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ $b['id'] }}</td>
                                <td data-label="Branch" class="px-3 py-2.5">
                                    <span class="block font-medium text-neutral-800">{{ $b['name'] }}</span>
                                    <span class="block text-xs text-neutral-500">{{ $b['city'] }}</span>
                                </td>
                                <td data-label="Manager" class="px-3 py-2.5 text-neutral-600">{{ $b['manager'] }}</td>
                                <td data-label="Opened" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ Format::date($b['opened']) }}</td>
                                <td data-label="Members" class="px-3 py-2.5 text-right tabular-nums text-neutral-700">{{ number_format($b['members']) }}</td>
                                <td data-label="Portfolio" class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800">{{ Format::peso($b['portfolio']) }}</td>
                                <td data-label="" class="px-3 py-2.5 text-right">
                                    <x-btn size="sm" icon="pencil">Edit</x-btn>
                                </td>
                            </tr>
                        @endforeach
                    </x-data-table>
                @endif
            </x-card>

            <x-card title="Add / edit branch">
                <form class="space-y-4" onsubmit="return false;">
                    <x-form-field label="Branch code" name="branch_code" value="BR-06" required tabular />
                    <x-form-field label="Branch name" name="branch_name" placeholder="e.g. Pulilan Branch" required />
                    <x-form-field label="City / Municipality" name="branch_city" placeholder="e.g. Pulilan, Bulacan" required />
                    <x-form-field label="Address" type="textarea" name="branch_address" rows="2"
                                  placeholder="Street, barangay, city, province" required />
                    <x-form-field label="Branch manager" type="select" name="branch_manager" required
                                  :options="collect($branches)->pluck('manager')->all()" placeholder="Select a manager" />
                    <x-form-field label="Date opened" type="date" name="branch_opened" value="2026-10-01" required tabular />
                    <x-form-field label="Contact number" name="branch_contact" placeholder="(044) 000 0000" tabular />

                    <div class="flex gap-2 pt-1">
                        <x-btn class="flex-1">Cancel</x-btn>
                        <x-btn class="flex-1" icon="plus">Add branch</x-btn>
                    </div>
                </form>
            </x-card>
        </div>

        {{-- ============================================================ Fiscal year --}}
        <div x-show="tab === 'fiscal'" x-cloak role="tabpanel" class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <x-card class="lg:col-span-2" title="Fiscal Year & Accounting Periods"
                    subtitle="Controls report periods, interest posting, and year-end closing">
                <form class="grid grid-cols-1 gap-5 sm:grid-cols-2" onsubmit="return false;">
                    <x-form-field label="Fiscal year start" type="select" name="fy_start"
                                  :options="['January', 'April', 'July', 'October']" value="January" required />
                    <x-form-field label="Current fiscal year" name="fy_current" value="FY 2026" required tabular />
                    <x-form-field label="Period start" type="date" name="period_start" value="2026-01-01" required tabular />
                    <x-form-field label="Period end" type="date" name="period_end" value="2026-12-31" required tabular />
                    <x-form-field label="Interest posting frequency" type="select" name="interest_frequency"
                                  :options="['Monthly', 'Quarterly', 'Semi-annually', 'Annually']" value="Quarterly" required />
                    <x-form-field label="Day-end cut-off time" type="time" name="cutoff" value="17:00" required tabular
                                  help="Transactions after this time post to the next business day." />
                </form>

                <div class="mt-5 border-t border-neutral-200 pt-5">
                    <h3 class="mb-3 text-[13px] font-semibold uppercase tracking-wide text-neutral-500">Accounting periods</h3>
                    <ul class="space-y-2">
                        @foreach([
                            ['Q1 2026 · Jan – Mar', 'Closed'],
                            ['Q2 2026 · Apr – Jun', 'Closed'],
                            ['Q3 2026 · Jul – Sep', 'Active'],
                            ['Q4 2026 · Oct – Dec', 'Upcoming'],
                        ] as [$period, $status])
                            <li class="flex items-center justify-between gap-3 rounded-[8px] border border-neutral-200 px-3.5 py-2.5">
                                <span class="text-[13px] text-neutral-700">{{ $period }}</span>
                                <x-status-badge :status="$status" />
                            </li>
                        @endforeach
                    </ul>
                </div>
            </x-card>

            <x-card title="Year-end closing">
                <p class="text-[13px] leading-relaxed text-neutral-600">
                    Closing the fiscal year locks all transactions in the period, posts final interest, and generates the
                    statutory reports required by the CDA.
                </p>

                <ul class="mt-4 space-y-2.5">
                    @foreach([
                        ['All quarters closed', false],
                        ['Interest posted for Q4', false],
                        ['Trial balance reconciled', false],
                        ['Loan loss provision booked', false],
                    ] as $index => [$item, $done])
                        <li class="flex items-start gap-2.5 text-[13px]">
                            <span @class([
                                'mt-0.5 grid h-4 w-4 shrink-0 place-items-center rounded-full',
                                'bg-success text-white' => $done,
                                'border border-neutral-300 bg-white' => ! $done,
                            ])>
                                @if($done)<x-icon name="check" class="h-3 w-3" stroke="3" />@endif
                            </span>
                            <span class="text-neutral-600">{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>

                <x-btn class="mt-5 w-full" icon="lock" disabled>Close FY 2026</x-btn>
                <p class="mt-2 text-xs text-neutral-500">Available once all prerequisites are met.</p>
            </x-card>
        </div>

        {{-- ============================================================ Notifications --}}
        <div x-show="tab === 'notifications'" x-cloak role="tabpanel" class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <x-card class="lg:col-span-2" title="Notification Rules" subtitle="Who gets told, and when">
                <div class="space-y-1">
                    @foreach([
                        ['Loan application submitted', 'Branch Manager', ['In-app' => true, 'Email' => true, 'SMS' => false]],
                        ['Application approved or rejected', 'Loan Officer', ['In-app' => true, 'Email' => true, 'SMS' => false]],
                        ['Account crosses 30 days past due', 'Loan Officer, Branch Manager', ['In-app' => true, 'Email' => true, 'SMS' => true]],
                        ['Disbursement released', 'Cashier, Branch Manager', ['In-app' => true, 'Email' => false, 'SMS' => false]],
                        ['Teller blotter variance detected', 'Branch Manager', ['In-app' => true, 'Email' => true, 'SMS' => true]],
                        ['Interest posting completed', 'Super Admin, Fleet Manager', ['In-app' => true, 'Email' => true, 'SMS' => false]],
                        ['Day not closed by cut-off', 'Branch Manager', ['In-app' => true, 'Email' => false, 'SMS' => true]],
                    ] as $ri => [$event, $audience, $channels])
                        <div class="flex flex-wrap items-center gap-4 rounded-[8px] px-3 py-3 {{ $ri % 2 ? 'bg-neutral-50' : '' }}">
                            <div class="min-w-0 flex-1">
                                <p class="text-[13px] font-medium text-neutral-800">{{ $event }}</p>
                                <p class="text-xs text-neutral-500">{{ $audience }}</p>
                            </div>
                            <div class="flex items-center gap-4">
                                @foreach($channels as $channel => $on)
                                    <label class="flex items-center gap-1.5 text-xs text-neutral-600">
                                        <input type="checkbox" @checked($on)
                                               class="h-4 w-4 rounded-[3px] border-neutral-300 text-primary-600"
                                               aria-label="{{ $channel }} notification for {{ $event }}">
                                        {{ $channel }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>

            <x-card title="SMS Gateway">
                <form class="space-y-4" onsubmit="return false;">
                    <x-form-field label="Provider" type="select" name="sms_provider"
                                  :options="['Semaphore', 'Movider', 'Twilio', 'iTexMo']" value="Semaphore" />
                    <x-form-field label="Sender name" name="sms_sender" value="LEDGER" required
                                  help="Must be registered with the provider." />
                    <x-form-field label="API key" type="password" name="sms_key" value="••••••••••••••••" required />
                    <x-form-field label="Monthly credit limit" name="sms_limit" prefix="₱" value="5,000.00" tabular />
                </form>

                <dl class="mt-5 space-y-4 border-t border-neutral-200 pt-4">
                    <x-kpi label="Credits used this month" :value="Format::peso(2840)" hint="of ₱5,000.00" />
                    <x-kpi label="Messages sent" value="1,420" />
                    <x-kpi label="Delivery rate" value="98.6%" />
                </dl>
            </x-card>
        </div>

        {{-- ============================================================ Security --}}
        <div x-show="tab === 'security'" x-cloak role="tabpanel" class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <x-card class="lg:col-span-2" title="Security Policy" subtitle="Applies to every staff account">
                <form class="grid grid-cols-1 gap-5 sm:grid-cols-2" onsubmit="return false;">
                    <x-form-field label="Minimum password length" name="pw_length" value="10" required tabular suffix="chars" />
                    <x-form-field label="Password expiry" type="select" name="pw_expiry"
                                  :options="['Never', 'Every 60 days', 'Every 90 days', 'Every 180 days']" value="Every 90 days" />
                    <x-form-field label="Failed sign-in lockout" name="lockout" value="5" required tabular suffix="tries" />
                    <x-form-field label="Lockout duration" type="select" name="lockout_duration"
                                  :options="['15 minutes', '30 minutes', '1 hour', 'Until unlocked by an admin']" value="30 minutes" />
                    <x-form-field label="Session idle timeout" type="select" name="idle_timeout"
                                  :options="['15 minutes', '30 minutes', '1 hour', '4 hours']" value="30 minutes" />
                    <x-form-field label="Audit log retention" type="select" name="audit_retention"
                                  :options="['1 year', '3 years', '5 years', 'Indefinite']" value="5 years" />
                </form>

                <div class="mt-5 space-y-3 border-t border-neutral-200 pt-5">
                    @foreach([
                        ['Require two-factor authentication for Super Admin and Branch Manager', true],
                        ['Restrict sign-in to registered branch IP addresses', true],
                        ['Require dual approval for waivers above ₱500.00', true],
                        ['Require dual approval for write-offs', true],
                        ['Allow password reset by email', false],
                    ] as [$policy, $on])
                        <label class="flex items-start gap-2.5">
                            <input type="checkbox" @checked($on) class="mt-0.5 h-4 w-4 rounded-[4px] border-neutral-300 text-primary-600">
                            <span class="text-[13px] text-neutral-700">{{ $policy }}</span>
                        </label>
                    @endforeach
                </div>
            </x-card>

            <x-card title="Recent Security Events">
                <ol class="space-y-4">
                    @foreach([
                        ['alert-triangle', 'danger', '3 failed sign-ins', 'dalonzo@ledger.coop.ph · 192.168.20.14', '2 hours ago'],
                        ['key-round', 'info', 'Password reset', 'gvillamor@ledger.coop.ph', 'Yesterday'],
                        ['lock', 'warning', 'Account locked', 'dalonzo@ledger.coop.ph', '2 days ago'],
                        ['shield-check', 'success', '2FA enabled', 'eramirez@ledger.coop.ph', '4 days ago'],
                    ] as [$icon, $tone, $title, $detail, $when])
                        <li class="flex gap-3">
                            <span @class([
                                'mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-full',
                                'bg-[#FEECEA] text-danger' => $tone === 'danger',
                                'bg-[#FEF0D6] text-warning' => $tone === 'warning',
                                'bg-[#E7F6EE] text-success' => $tone === 'success',
                                'bg-[#EAF2FE] text-info' => $tone === 'info',
                            ])>
                                <x-icon :name="$icon" class="h-4 w-4" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-[13px] font-medium text-neutral-800">{{ $title }}</p>
                                <p class="truncate text-xs tabular-nums text-neutral-500">{{ $detail }}</p>
                                <p class="text-xs text-neutral-400">{{ $when }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>

                <x-btn class="mt-5 w-full" icon="history" :href="route('admin.audit')">View full audit log</x-btn>
            </x-card>
        </div>
    </x-tabs>
</div>
