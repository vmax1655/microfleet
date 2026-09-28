@php
    use App\Support\Format;

    // Column accents follow the badge tone for each stage.
    $stageAccent = [
        'Submitted' => 'border-t-[#B0CDFA]',
        'Under Review' => 'border-t-[#F5D28A]',
        'Credit Assessment' => 'border-t-[#F5D28A]',
        'Approved' => 'border-t-[#A6E0C1]',
        'Rejected' => 'border-t-[#F5B5AE]',
    ];

    $requested = array_sum(array_column($applications, 'amount'));
@endphp

<div x-data="{ view: 'board' }">
    <x-breadcrumb />

    <x-page-header
        title="Loan Applications"
        subtitle="Every application in flight, from submission through to approval or rejection.">
        <x-slot:actions>
            <x-segmented :options="[
                ['key' => 'board', 'label' => 'Pipeline', 'icon' => 'columns-3'],
                ['key' => 'table', 'label' => 'Table', 'icon' => 'list'],
            ]" model="view" label="Switch between pipeline board and table view" />
            <x-btn icon="download">Export</x-btn>
            <x-btn variant="primary" icon="plus">New Application</x-btn>
        </x-slot:actions>
    </x-page-header>

    <section aria-label="Pipeline summary" class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="In Pipeline" :value="count($applications)" :delta="7.1" icon="file-text" />
        <x-stat-card label="Total Requested" :value="Format::pesoCompact($requested)" :delta="5.9" icon="wallet" />
        <x-stat-card label="Approval Rate" value="82.4%" :delta="1.8" icon="check-circle" />
        <x-stat-card label="Avg. Days to Decision" value="4.2" :delta="0.6" good-direction="down" icon="clock" />
    </section>

    <x-filter-bar search-label="Search applications" search-placeholder="Application ID, member name, or member ID…" date-range>
        <x-select label="Product" :options="collect($products)->pluck('name')->all()" placeholder="All products" width="w-48" />
        <x-select label="Loan officer" :options="collect($officers)->pluck('name')->all()" placeholder="All officers" width="w-44" />
        <x-select label="Amount range" :options="['₱5,000 – ₱25,000', '₱25,001 – ₱50,000', '₱50,001 – ₱100,000', '₱100,001 and above']" placeholder="Any amount" width="w-48" />
    </x-filter-bar>

    {{-- ================================================================ Pipeline board --}}
    <div x-show="view === 'board'" x-cloak>
        <div class="-mx-1 overflow-x-auto pb-2">
            <div class="flex min-w-max gap-4 px-1">
                @foreach($stages as $stage)
                    @php $cards = $board[$stage]; @endphp

                    <section class="w-[290px] shrink-0" aria-label="{{ $stage }} column">
                        <header class="flex items-center justify-between gap-2 rounded-t-[12px] border border-b-0 border-neutral-200 border-t-4 bg-white px-3.5 py-3 {{ $stageAccent[$stage] }}">
                            <h2 class="text-[13px] font-semibold text-neutral-800">{{ $stage }}</h2>
                            <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-neutral-600">
                                {{ count($cards) }}
                            </span>
                        </header>

                        <div class="space-y-2.5 rounded-b-[12px] border border-neutral-200 bg-neutral-100/60 p-2.5">
                            @forelse($cards as $app)
                                <article class="rounded-[8px] border border-neutral-200 bg-white p-3.5 shadow-card">
                                    <div class="flex items-start gap-2.5">
                                        <x-avatar :name="$app['member']" size="sm" />
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-[13px] font-medium text-neutral-800">{{ $app['member'] }}</p>
                                            <p class="truncate text-xs tabular-nums text-neutral-500">{{ $app['id'] }}</p>
                                        </div>
                                    </div>

                                    <p class="mt-3 text-lg font-semibold tabular-nums text-neutral-900">{{ Format::peso($app['amount']) }}</p>
                                    <p class="mt-0.5 truncate text-xs text-neutral-500">{{ $app['product'] }} · {{ $app['term'] }} mo · {{ $app['frequency'] }}</p>

                                    <div class="mt-3 flex items-center justify-between gap-2 border-t border-neutral-200 pt-2.5">
                                        <span class="flex min-w-0 items-center gap-1.5 text-xs text-neutral-500">
                                            <x-icon name="user" class="h-3.5 w-3.5 shrink-0" />
                                            <span class="truncate">{{ $app['officer'] }}</span>
                                        </span>
                                        <span class="flex shrink-0 items-center gap-1 text-xs tabular-nums {{ $app['days_in_stage'] > 7 ? 'text-danger' : 'text-neutral-500' }}">
                                            <x-icon name="clock" class="h-3.5 w-3.5" />
                                            {{ $app['days_in_stage'] }}d
                                        </span>
                                    </div>

                                    <div class="mt-2.5">
                                        <x-btn size="sm" class="w-full" icon="eye" :href="route('loans.assessment')">Open</x-btn>
                                    </div>
                                </article>
                            @empty
                                <div class="rounded-[8px] border border-dashed border-neutral-300 px-3 py-8 text-center">
                                    <x-icon name="inbox" class="mx-auto h-5 w-5 text-neutral-400" />
                                    <p class="mt-2 text-xs text-neutral-500">Nothing in {{ strtolower($stage) }}</p>
                                </div>
                            @endforelse
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ================================================================ Table view --}}
    <div x-show="view === 'table'" x-cloak>
        <x-card flush>
            @if(count($applications) === 0)
                <x-empty-state
                    icon="file-text"
                    heading="No applications match these filters"
                    help="Applications appear here as soon as a loan officer submits them from the field."
                    action-label="New Application" />
            @else
                <x-data-table sort-key="submitted" sort-dir="desc" caption="All loan applications">
                    <x-slot:head>
                        <x-th sort="app">Application</x-th>
                        <x-th sort="member">Member</x-th>
                        <x-th sort="product">Product</x-th>
                        <x-th sort="amount" align="right">Amount</x-th>
                        <x-th sort="term" align="right">Term</x-th>
                        <x-th sort="officer">Officer</x-th>
                        <x-th sort="submitted">Submitted</x-th>
                        <x-th sort="days" align="right">Days in Stage</x-th>
                        <x-th sort="stage">Stage</x-th>
                        <x-th align="right" sr-only>Actions</x-th>
                    </x-slot:head>

                    @foreach($applications as $i => $app)
                        <tr data-row data-app="{{ $app['id'] }}" data-member="{{ $app['member'] }}"
                            data-product="{{ $app['product'] }}" data-amount="{{ $app['amount'] }}"
                            data-term="{{ $app['term'] }}" data-officer="{{ $app['officer'] }}"
                            data-submitted="{{ $app['submitted'] }}" data-days="{{ $app['days_in_stage'] }}"
                            data-stage="{{ $app['stage'] }}"
                            class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">

                            <td data-label="Application" class="px-3 py-2.5">
                                <a href="{{ route('loans.assessment') }}" class="font-medium tabular-nums text-primary-700 hover:underline">{{ $app['id'] }}</a>
                            </td>

                            <td data-label="Member" class="px-3 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <x-avatar :name="$app['member']" size="sm" />
                                    <div class="min-w-0">
                                        <span class="block truncate font-medium text-neutral-800">{{ $app['member'] }}</span>
                                        <span class="block truncate text-xs text-neutral-500">{{ $app['center'] }}</span>
                                    </div>
                                </div>
                            </td>

                            <td data-label="Product" class="px-3 py-2.5 text-neutral-700">{{ $app['product'] }}</td>
                            <td data-label="Amount" class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800">{{ Format::peso($app['amount']) }}</td>
                            <td data-label="Term" class="px-3 py-2.5 text-right tabular-nums text-neutral-600">{{ $app['term'] }} mo</td>
                            <td data-label="Officer" class="px-3 py-2.5 text-neutral-600">{{ $app['officer'] }}</td>
                            <td data-label="Submitted" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ Format::date($app['submitted']) }}</td>
                            <td data-label="Days in Stage" class="px-3 py-2.5 text-right tabular-nums {{ $app['days_in_stage'] > 7 ? 'font-medium text-danger' : 'text-neutral-600' }}">{{ $app['days_in_stage'] }}</td>
                            <td data-label="Stage" class="px-3 py-2.5"><x-status-badge :status="$app['stage']" /></td>

                            <td data-label="" class="px-3 py-2.5">
                                <x-row-actions :label="'Actions for '.$app['id']">
                                    <x-row-action icon="eye" :href="route('loans.assessment')">Open assessment</x-row-action>
                                    <x-row-action icon="user" :href="route('membership.profile', $app['member_id'])">View member</x-row-action>
                                    <x-row-action icon="check">Approve</x-row-action>
                                    <x-row-action icon="send">Request more info</x-row-action>
                                    <x-row-action icon="x" danger>Reject</x-row-action>
                                </x-row-actions>
                            </td>
                        </tr>
                    @endforeach
                </x-data-table>

                <x-pagination :from="1" :to="count($applications)" :total="148" :current="1" :per-page="22" />
            @endif
        </x-card>
    </div>
</div>
