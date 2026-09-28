@php use App\Support\Format; @endphp

{{--
    Read-only by design. Muted styling and the absence of any row action signal
    that entries here can never be edited or deleted.
--}}
<div x-data="{ view: 'table' }">
    <x-breadcrumb />

    <x-page-header
        title="Audit Log"
        subtitle="An immutable record of every action taken in the system. Entries cannot be edited or removed.">
        <x-slot:actions>
            <x-segmented :options="[
                ['key' => 'table', 'label' => 'Table', 'icon' => 'list'],
                ['key' => 'timeline', 'label' => 'Timeline', 'icon' => 'activity'],
            ]" model="view" label="Switch between table and timeline view" />
            <x-btn variant="primary" icon="download">Export Log</x-btn>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-5 flex items-start gap-2.5 rounded-[12px] border border-neutral-200 bg-neutral-100 p-4">
        <x-icon name="lock" class="mt-0.5 shrink-0 text-neutral-500" />
        <p class="text-[13px] text-neutral-600">
            This log is append-only and retained for 5 years, per the security policy in Branches &amp; Settings.
            Exports are watermarked with the requesting user and timestamp.
        </p>
    </div>

    <x-filter-bar search-label="Search log" search-placeholder="Actor, action, record ID, or IP address…" date-range>
        <x-select label="Module" :options="$modules" placeholder="All modules" width="w-48" />
        <x-select label="Actor" :options="$actors" placeholder="All actors" width="w-48" />
        <x-select label="Action type" :options="['Created', 'Updated', 'Approved', 'Rejected', 'Deleted', 'Exported', 'Signed in']" placeholder="All actions" width="w-44" />
    </x-filter-bar>

    {{-- ================================================================ Table view --}}
    <div x-show="view === 'table'" x-cloak>
        <x-card flush>
            @if(count($entries) === 0)
                <x-empty-state
                    icon="history"
                    heading="No audit entries in this range"
                    help="Widen the date range or clear the module and actor filters."
                    action-label="Clear filters"
                    action-icon="refresh-cw" />
            @else
                <x-data-table sort-key="timestamp" sort-dir="desc" caption="System audit log">
                    <x-slot:head>
                        <x-th sort="timestamp">Timestamp</x-th>
                        <x-th sort="actor">Actor</x-th>
                        <x-th sort="action">Action</x-th>
                        <x-th sort="module">Module</x-th>
                        <x-th sort="ip">IP Address</x-th>
                        <x-th>Before / After</x-th>
                    </x-slot:head>

                    @foreach($entries as $i => $e)
                        <tr data-row data-timestamp="{{ $e['timestamp'] }}" data-actor="{{ $e['actor'] }}"
                            data-action="{{ $e['action'] }}" data-module="{{ $e['module'] }}" data-ip="{{ $e['ip'] }}"
                            class="{{ $i % 2 ? 'bg-neutral-50' : '' }}">

                            <td data-label="Timestamp" class="whitespace-nowrap px-3 py-2.5 tabular-nums text-neutral-600">{{ $e['timestamp'] }}</td>

                            <td data-label="Actor" class="px-3 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <x-avatar :name="$e['actor']" size="xs" tone="neutral" />
                                    <span class="truncate text-neutral-700">{{ $e['actor'] }}</span>
                                </div>
                            </td>

                            <td data-label="Action" class="px-3 py-2.5 font-medium text-neutral-700">{{ $e['action'] }}</td>

                            <td data-label="Module" class="px-3 py-2.5">
                                <span class="block text-neutral-600">{{ $e['module'] }}</span>
                                <span class="block text-xs text-neutral-500">{{ $e['submodule'] }}</span>
                            </td>

                            <td data-label="IP Address" class="px-3 py-2.5 tabular-nums text-neutral-500">{{ $e['ip'] }}</td>
                            <td data-label="Before / After" class="px-3 py-2.5 text-[13px] text-neutral-600">{{ $e['detail'] }}</td>
                        </tr>
                    @endforeach
                </x-data-table>

                <x-pagination :from="1" :to="count($entries)" :total="24816" :current="1" :per-page="12" />
            @endif
        </x-card>
    </div>

    {{-- ================================================================ Timeline view --}}
    <div x-show="view === 'timeline'" x-cloak>
        <x-card title="Activity Timeline" subtitle="Same entries, ordered newest first">
            <ol class="relative space-y-6 border-l border-neutral-200 pl-6">
                @foreach($entries as $e)
                    <li class="relative">
                        <span class="absolute -left-[31px] grid h-6 w-6 place-items-center rounded-full bg-neutral-200 text-neutral-600 ring-4 ring-white">
                            <x-icon name="history" class="h-3.5 w-3.5" />
                        </span>

                        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                            <p class="text-[13px] text-neutral-700">
                                <span class="font-medium text-neutral-900">{{ $e['actor'] }}</span>
                                <span class="text-neutral-600">{{ strtolower($e['action']) }}</span>
                            </p>
                            <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-600">
                                {{ $e['module'] }} · {{ $e['submodule'] }}
                            </span>
                        </div>

                        <p class="mt-1 text-[13px] text-neutral-600">{{ $e['detail'] }}</p>

                        <p class="mt-1 flex flex-wrap items-center gap-x-2 text-xs tabular-nums text-neutral-400">
                            <span>{{ $e['timestamp'] }}</span>
                            <span aria-hidden="true">·</span>
                            <span>{{ $e['ip'] }}</span>
                            <span aria-hidden="true">·</span>
                            <span>{{ $e['id'] }}</span>
                        </p>
                    </li>
                @endforeach
            </ol>
        </x-card>
    </div>
</div>
