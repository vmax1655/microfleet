@php
    use App\Support\Format;

    $ids = collect($members)->pluck('id')->all();
@endphp

<div>
    <x-breadcrumb />

    <x-page-header
        title="Member Directory"
        subtitle="All registered members across the branch, with their active loans and outstanding balances.">
        <x-slot:actions>
            <x-btn icon="upload">Import CSV</x-btn>
            <x-btn variant="primary" icon="plus" :href="route('membership.registration')">Add Member</x-btn>
        </x-slot:actions>
    </x-page-header>

    {{-- KPI strip --}}
    <section aria-label="Membership summary" class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Total Members" :value="number_format($stats['total'])" :delta="3.6" icon="users" />
        <x-stat-card label="Active Members" :value="number_format($stats['active'])" :delta="2.8" icon="user-check" />
        <x-stat-card label="New This Month" :value="number_format($stats['newThisMonth'])" :delta="12.4" icon="user-plus" />
        <x-stat-card label="Dormant Members" :value="number_format($stats['dormant'])" :delta="4.1" good-direction="down" icon="clock" />
    </section>

    <x-filter-bar search-label="Search members" search-placeholder="Name, member ID, or mobile number…">
        <x-select label="Branch" :options="collect($branches)->pluck('name')->all()" placeholder="All branches" width="w-48" />
        <x-select label="Center" :options="collect($centers)->pluck('name')->all()" placeholder="All centers" width="w-52" />
        <x-select label="Status" :options="['Active', 'Pending', 'Overdue', 'Dormant', 'Closed']" placeholder="All statuses" width="w-36" />
        <x-select label="Loan officer" :options="collect($officers)->pluck('name')->all()" placeholder="All officers" width="w-44" />
    </x-filter-bar>

    <x-card flush x-data="bulkSelect(@js($ids))">
        {{-- Bulk action bar appears only when rows are selected --}}
        <div x-show="count() > 0" x-cloak
             class="flex flex-wrap items-center gap-3 border-b border-primary-200 bg-primary-50 px-4 py-2.5">
            <p class="text-[13px] font-medium text-primary-800">
                <span x-text="count()">0</span> selected
            </p>
            <div class="flex flex-wrap items-center gap-2">
                <x-btn size="sm" icon="send">Send SMS</x-btn>
                <x-btn size="sm" icon="download">Export selection</x-btn>
                <x-btn size="sm" variant="danger-outline" icon="folder-open"
                       @click="$dispatch('open-modal', 'archive-members')">Archive</x-btn>
            </div>
            <button type="button" @click="selected = []" class="ml-auto text-[13px] font-medium text-primary-700 hover:underline">
                Clear selection
            </button>
        </div>

        @if(count($members) === 0)
            <x-empty-state
                icon="users"
                heading="No members match these filters"
                help="Try widening the branch or center filter, or register the member if they are new to the cooperative."
                action-label="Add Member"
                :action-href="route('membership.registration')" />
        @else
            <x-data-table sort-key="name" caption="Directory of cooperative members">
                <x-slot:head>
                    <th scope="col" class="w-10 px-3 py-2.5">
                        <input type="checkbox"
                               @change="toggleAll($event)"
                               :checked="allChecked"
                               :indeterminate="someChecked"
                               aria-label="Select all members on this page">
                    </th>
                    <x-th sort="name">Member</x-th>
                    <x-th sort="id">Member ID</x-th>
                    <x-th sort="contact">Contact</x-th>
                    <x-th sort="center">Group / Center</x-th>
                    <x-th sort="loans" align="right">Active Loans</x-th>
                    <x-th sort="outstanding" align="right">Total Outstanding</x-th>
                    <x-th sort="status">Status</x-th>
                    <x-th align="right" sr-only>Actions</x-th>
                </x-slot:head>

                @foreach($members as $i => $m)
                    <tr data-row
                        data-name="{{ $m['name'] }}"
                        data-id="{{ $m['id'] }}"
                        data-contact="{{ $m['contact'] }}"
                        data-center="{{ $m['center'] }}"
                        data-loans="{{ $m['active_loans'] }}"
                        data-outstanding="{{ $m['outstanding'] }}"
                        data-status="{{ $m['status'] }}"
                        class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">

                        <td data-label="" class="px-3 py-2.5">
                            <input type="checkbox"
                                   value="{{ $m['id'] }}"
                                   x-model="selected"
                                   aria-label="Select {{ $m['name'] }}">
                        </td>

                        <td data-label="Member" class="px-3 py-2.5">
                            <div class="flex items-center gap-2.5">
                                <x-avatar :name="$m['name']" size="sm" />
                                <div class="min-w-0">
                                    <a href="{{ route('membership.profile', $m['id']) }}"
                                       class="block truncate font-medium text-neutral-800 hover:text-primary-700 hover:underline">{{ $m['name'] }}</a>
                                    <span class="block truncate text-xs text-neutral-500">{{ $m['livelihood'] }}</span>
                                </div>
                            </div>
                        </td>

                        <td data-label="Member ID" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ $m['id'] }}</td>

                        <td data-label="Contact" class="px-3 py-2.5">
                            <span class="block tabular-nums text-neutral-700">{{ $m['contact'] }}</span>
                            <span class="block truncate text-xs text-neutral-500">{{ $m['email'] }}</span>
                        </td>

                        <td data-label="Group / Center" class="px-3 py-2.5">
                            <span class="block text-neutral-700">{{ $m['center'] }}</span>
                            <span class="block text-xs text-neutral-500">{{ $m['officer'] }}</span>
                        </td>

                        <td data-label="Active Loans" class="px-3 py-2.5 text-right tabular-nums text-neutral-700">{{ $m['active_loans'] }}</td>

                        <td data-label="Total Outstanding" class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800">
                            {{ Format::peso($m['outstanding']) }}
                        </td>

                        <td data-label="Status" class="px-3 py-2.5"><x-status-badge :status="$m['status']" /></td>

                        <td data-label="" class="px-3 py-2.5">
                            <x-row-actions :label="'Actions for '.$m['name']">
                                <x-row-action icon="eye" :href="route('membership.profile', $m['id'])">View profile</x-row-action>
                                <x-row-action icon="pencil">Edit details</x-row-action>
                                <x-row-action icon="file-text" :href="route('loans.applications')">New loan application</x-row-action>
                                <x-row-action icon="shield-check" :href="route('membership.kyc')">Review KYC</x-row-action>
                                <x-row-action icon="send">Send SMS reminder</x-row-action>
                                <x-row-action icon="folder-open" danger
                                              @click="$dispatch('open-modal', 'archive-members')">Archive member</x-row-action>
                            </x-row-actions>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>

            <x-pagination :from="1" :to="count($members)" :total="1248" :current="1" :per-page="32" />
        @endif
    </x-card>

    {{-- Loading skeleton reference (rendered by wire:loading in the live app) --}}
    <template x-if="false">
        <x-card class="mt-5" flush>
            <x-skeleton.table :rows="6" :cols="7" />
        </x-card>
    </template>

    <x-modal name="archive-members" title="Archive selected members?" tone="danger" icon="alert-triangle"
             subtitle="Archived members keep their history but can no longer take new loans.">
        <p class="text-sm text-neutral-600">
            Members with an outstanding balance cannot be archived. Any such rows in your selection will be skipped
            and reported back to you.
        </p>
        <x-form-field class="mt-4" label="Reason" type="textarea" name="reason" rows="2" required
                      placeholder="e.g. Relocated outside the service area" />

        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="danger" icon="folder-open">Archive members</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
