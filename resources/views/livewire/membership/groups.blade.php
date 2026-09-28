@php
    use App\Support\Format;

    $totalMembers = array_sum(array_column($centers, 'members'));
    $totalPortfolio = array_sum(array_column($centers, 'portfolio'));
    $avgRate = round(collect($centers)->avg('repayment_rate'), 1);
@endphp

<div>
    <x-breadcrumb />

    <x-page-header
        title="Groups & Centers"
        subtitle="Weekly meeting centers and the solidarity groups that sit under them.">
        <x-slot:actions>
            <x-btn icon="download">Export</x-btn>
            <x-btn variant="primary" icon="plus">Create Center</x-btn>
        </x-slot:actions>
    </x-page-header>

    <section aria-label="Center summary" class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Active Centers" :value="count($centers)" :delta="0.0" icon="map-pin" />
        <x-stat-card label="Members in Centers" :value="number_format($totalMembers)" :delta="3.2" icon="users" />
        <x-stat-card label="Center Portfolio" :value="Format::pesoCompact($totalPortfolio)" :delta="4.6" icon="wallet" />
        <x-stat-card label="Avg. Repayment Rate" :value="Format::percent($avgRate)" :delta="0.4" icon="target" />
    </section>

    <x-filter-bar search-label="Search centers" search-placeholder="Center name or barangay…">
        <x-select label="Meeting day" :options="['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']" placeholder="Any day" width="w-36" />
        <x-select label="Loan officer" :options="collect($officers)->pluck('name')->all()" placeholder="All officers" width="w-44" />
        <x-select label="Sort by" :options="['Portfolio (high to low)', 'Repayment rate', 'Member count', 'Center name']" width="w-52" />
    </x-filter-bar>

    @if(count($centers) === 0)
        <x-card>
            <x-empty-state
                icon="map-pin"
                heading="No centers yet"
                help="Centers group members by barangay and set the weekly meeting schedule for collections."
                action-label="Create Center" />
        </x-card>
    @else
        <ul class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($centers as $center)
                <li>
                    <article class="flex h-full flex-col rounded-[12px] border border-neutral-200 bg-white p-5 shadow-card transition-shadow hover:shadow-flyout">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="truncate text-[15px] font-semibold text-neutral-800">
                                    <a href="{{ route('membership.groups.show', $center['id']) }}" class="hover:text-primary-700 hover:underline">
                                        {{ $center['name'] }}
                                    </a>
                                </h2>
                                <p class="mt-0.5 text-xs tabular-nums text-neutral-500">{{ $center['id'] }} · {{ $center['branch'] }}</p>
                            </div>
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-[8px] bg-primary-50 text-primary-700">
                                <x-icon name="map-pin" />
                            </span>
                        </div>

                        <p class="mt-3 inline-flex items-center gap-1.5 text-[13px] text-neutral-600">
                            <x-icon name="calendar" class="h-4 w-4 text-neutral-400" />
                            {{ $center['meeting_day'] }}s at {{ $center['meeting_time'] }}
                        </p>

                        <dl class="mt-4 grid grid-cols-2 gap-4 border-t border-neutral-200 pt-4">
                            <x-kpi label="Members" :value="$center['members']" />
                            <x-kpi label="Portfolio" :value="Format::pesoCompact($center['portfolio'])" />
                        </dl>

                        <div class="mt-4">
                            <x-meter :value="$center['repayment_rate']" label="Repayment rate" />
                        </div>

                        <div class="mt-4 flex items-center gap-2.5 border-t border-neutral-200 pt-4">
                            <x-avatar :name="$center['officer']" size="sm" tone="accent" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[13px] font-medium text-neutral-800">{{ $center['officer'] }}</p>
                                <p class="text-xs text-neutral-500">Assigned officer</p>
                            </div>
                            <x-btn size="sm" icon="chevron-right" :href="route('membership.groups.show', $center['id'])"
                                   aria-label="Open {{ $center['name'] }}" />
                        </div>
                    </article>
                </li>
            @endforeach
        </ul>
    @endif
</div>
