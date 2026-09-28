@php
    use App\Support\Format;

    $trail = [
        ['label' => 'Membership', 'route' => 'membership.directory'],
        ['label' => 'Groups & Centers', 'route' => 'membership.groups'],
        ['label' => $center['name']],
    ];

    $rosterOutstanding = array_sum(array_column($roster, 'outstanding'));
    $rosterSavings = array_sum(array_column($roster, 'savings'));
@endphp

<div>
    <x-breadcrumb :trail="$trail" />

    <x-page-header
        :title="$center['name']"
        :subtitle="$center['branch'].' · Meets '.$center['meeting_day'].'s at '.$center['meeting_time'].' · '.$center['officer']"
        :back="route('membership.groups')"
        back-label="All centers">
        <x-slot:actions>
            <x-btn icon="printer">Print roster</x-btn>
            <x-btn icon="user-plus">Add member</x-btn>
            <x-btn variant="primary" icon="clipboard-list" :href="route('collections.sheet')">Open Collection Sheet</x-btn>
        </x-slot:actions>
    </x-page-header>

    <section aria-label="Center summary" class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Members" :value="$center['members']" :delta="2.9" icon="users" />
        <x-stat-card label="Center Portfolio" :value="Format::pesoCompact($center['portfolio'])" :delta="5.1" icon="wallet" />
        <x-stat-card label="Repayment Rate" :value="Format::percent($center['repayment_rate'])" :delta="0.6" icon="target" />
        <x-stat-card label="Savings Held" :value="Format::pesoCompact($rosterSavings * 3)" :delta="3.8" icon="piggy-bank" />
    </section>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

        {{-- Roster --}}
        <x-card class="lg:col-span-2" flush title="Member Roster"
                :subtitle="count($roster).' members currently enrolled in this center'">
            @if(count($roster) === 0)
                <x-empty-state
                    icon="users"
                    heading="No members in this center"
                    help="Assign members to this center from the directory, or register new members directly."
                    action-label="Add member"
                    :action-href="route('membership.registration')" />
            @else
                <x-data-table sort-key="name" caption="Members enrolled in this center">
                    <x-slot:head>
                        <x-th sort="name">Member</x-th>
                        <x-th sort="group">Group</x-th>
                        <x-th sort="loans" align="right">Loans</x-th>
                        <x-th sort="outstanding" align="right">Outstanding</x-th>
                        <x-th sort="ontime" align="right">On-time</x-th>
                        <x-th sort="status">Status</x-th>
                    </x-slot:head>

                    @foreach($roster as $i => $m)
                        <tr data-row data-name="{{ $m['name'] }}" data-group="Group {{ chr(65 + ($i % 4)) }}"
                            data-loans="{{ $m['active_loans'] }}" data-outstanding="{{ $m['outstanding'] }}"
                            data-ontime="{{ $m['on_time_rate'] }}" data-status="{{ $m['status'] }}"
                            class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">

                            <td data-label="Member" class="px-3 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <x-avatar :name="$m['name']" size="sm" />
                                    <div class="min-w-0">
                                        <a href="{{ route('membership.profile', $m['id']) }}"
                                           class="block truncate font-medium text-neutral-800 hover:text-primary-700 hover:underline">{{ $m['name'] }}</a>
                                        <span class="block text-xs tabular-nums text-neutral-500">{{ $m['id'] }}</span>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Group" class="px-3 py-2.5 text-neutral-600">Group {{ chr(65 + ($i % 4)) }}</td>
                            <td data-label="Loans" class="px-3 py-2.5 text-right tabular-nums text-neutral-700">{{ $m['active_loans'] }}</td>
                            <td data-label="Outstanding" class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800">{{ Format::peso($m['outstanding']) }}</td>
                            <td data-label="On-time" class="px-3 py-2.5 text-right tabular-nums text-neutral-600">{{ Format::percent($m['on_time_rate']) }}</td>
                            <td data-label="Status" class="px-3 py-2.5"><x-status-badge :status="$m['status']" /></td>
                        </tr>
                    @endforeach

                    <x-slot:foot>
                        <tr>
                            <td data-label="" class="px-3 py-2.5 text-[13px] font-semibold text-neutral-700">Total</td>
                            <td data-label="" class="px-3 py-2.5"></td>
                            <td data-label="Loans" class="px-3 py-2.5 text-right tabular-nums text-neutral-800">{{ array_sum(array_column($roster, 'active_loans')) }}</td>
                            <td data-label="Outstanding" class="px-3 py-2.5 text-right font-semibold tabular-nums text-neutral-900">{{ Format::peso($rosterOutstanding) }}</td>
                            <td data-label="" class="px-3 py-2.5"></td>
                            <td data-label="" class="px-3 py-2.5"></td>
                        </tr>
                    </x-slot:foot>
                </x-data-table>
            @endif
        </x-card>

        <div class="space-y-5">
            <x-card title="Group Loan Performance" subtitle="Amount due vs collected, last 6 months">
                <x-charts.group-performance height="h-64" />
            </x-card>

            <x-card title="Meeting Details">
                <dl class="space-y-4">
                    <x-kpi label="Meeting day" :value="$center['meeting_day']" :mono="false" />
                    <x-kpi label="Meeting time" :value="$center['meeting_time']" />
                    <x-kpi label="Venue" value="Brgy. Hall covered court" :mono="false" />
                    <x-kpi label="Center chief" :value="$roster[0]['name']" :mono="false" />
                    <x-kpi label="Assigned officer" :value="$center['officer']" :mono="false" />
                    <x-kpi label="Average attendance" value="94.0%" />
                </dl>
            </x-card>

            <x-card title="Solidarity Groups">
                <ul class="space-y-3">
                    @foreach(['Group A – Masagana', 'Group B – Maunlad', 'Group C – Matatag', 'Group D – Malaya'] as $gi => $group)
                        <li class="flex items-center gap-3 rounded-[8px] border border-neutral-200 p-3">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-primary-50 text-[13px] font-semibold text-primary-700">
                                {{ chr(65 + $gi) }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[13px] font-medium text-neutral-800">{{ $group }}</p>
                                <p class="text-xs tabular-nums text-neutral-500">{{ 5 + $gi }} members · {{ Format::pesoCompact(180000 + $gi * 42000) }}</p>
                            </div>
                            <x-status-badge :status="$gi === 3 ? 'Pending' : 'Active'" />
                        </li>
                    @endforeach
                </ul>
            </x-card>
        </div>
    </div>
</div>
