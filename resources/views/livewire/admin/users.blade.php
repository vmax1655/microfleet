@php
    use App\Support\Format;

    $permAbbr = ['view' => 'V', 'create' => 'C', 'edit' => 'E', 'delete' => 'D'];
@endphp

<div>
    <x-breadcrumb />

    <x-page-header
        title="Users & Roles"
        subtitle="Staff accounts and what each role is allowed to do in every sub-module.">
        <x-slot:actions>
            <x-btn icon="download">Export</x-btn>
            <x-btn variant="primary" icon="user-plus" @click="$dispatch('open-drawer', 'user-form')">Add User</x-btn>
        </x-slot:actions>
    </x-page-header>

    <section aria-label="User summary" class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Total Users" :value="count($users)" :delta="0.0" icon="users" />
        <x-stat-card label="Active Users" :value="collect($users)->where('status', 'Active')->count()" :delta="2.1" icon="user-check" />
        <x-stat-card label="Roles Defined" :value="count($roles)" :delta="0.0" icon="shield" />
        <x-stat-card label="Sign-ins Today" value="7" :delta="8.4" icon="log-out" />
    </section>

    <x-tabs :tabs="[
        ['key' => 'users', 'label' => 'Users', 'icon' => 'users', 'count' => count($users)],
        ['key' => 'matrix', 'label' => 'Role Permissions', 'icon' => 'shield-check'],
    ]">

        {{-- ============================================================ Users --}}
        <div x-show="tab === 'users'" role="tabpanel">
            <x-filter-bar search-label="Search users" search-placeholder="Name or email address…">
                <x-select label="Role" :options="$roles" placeholder="All roles" width="w-44" />
                <x-select label="Branch" :options="collect($branches)->pluck('name')->all()" placeholder="All branches" width="w-52" />
                <x-select label="Status" :options="['Active', 'Inactive']" placeholder="All statuses" width="w-36" />
            </x-filter-bar>

            <x-card flush>
                @if(count($users) === 0)
                    <x-empty-state
                        icon="users"
                        heading="No users match these filters"
                        help="Staff accounts control who can sign in and what they can act on."
                        action-label="Add User" />
                @else
                    <x-data-table sort-key="name" caption="System user accounts">
                        <x-slot:head>
                            <x-th sort="name">User</x-th>
                            <x-th sort="role">Role</x-th>
                            <x-th sort="branch">Branch</x-th>
                            <x-th sort="lastlogin">Last Sign-in</x-th>
                            <x-th sort="status">Status</x-th>
                            <x-th align="right" sr-only>Actions</x-th>
                        </x-slot:head>

                        @foreach($users as $i => $u)
                            <tr data-row data-name="{{ $u['name'] }}" data-role="{{ $u['role'] }}"
                                data-branch="{{ $u['branch'] }}" data-lastlogin="{{ $u['last_login'] }}"
                                data-status="{{ $u['status'] }}"
                                class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">

                                <td data-label="User" class="px-3 py-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <x-avatar :name="$u['name']" size="sm" />
                                        <div class="min-w-0">
                                            <span class="block truncate font-medium text-neutral-800">{{ $u['name'] }}</span>
                                            <span class="block truncate text-xs text-neutral-500">{{ $u['email'] }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td data-label="Role" class="px-3 py-2.5">
                                    <span class="inline-flex items-center gap-1.5 text-neutral-700">
                                        <x-icon name="shield" class="h-4 w-4 text-neutral-400" />
                                        {{ $u['role'] }}
                                    </span>
                                </td>

                                <td data-label="Branch" class="px-3 py-2.5 text-neutral-600">{{ $u['branch'] }}</td>
                                <td data-label="Last Sign-in" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ $u['last_login'] }}</td>
                                <td data-label="Status" class="px-3 py-2.5"><x-status-badge :status="$u['status']" /></td>

                                <td data-label="" class="px-3 py-2.5">
                                    <x-row-actions :label="'Actions for '.$u['name']">
                                        <x-row-action icon="pencil" @click="$dispatch('open-drawer', 'user-form')">Edit user</x-row-action>
                                        <x-row-action icon="key-round">Reset password</x-row-action>
                                        <x-row-action icon="shield">Change role</x-row-action>
                                        <x-row-action icon="history" :href="route('admin.audit')">View activity</x-row-action>
                                        <x-row-action icon="lock" danger @click="$dispatch('open-modal', 'deactivate-user')">Deactivate</x-row-action>
                                    </x-row-actions>
                                </td>
                            </tr>
                        @endforeach
                    </x-data-table>

                    <x-pagination :from="1" :to="count($users)" :total="count($users)" :current="1" />
                @endif
            </x-card>
        </div>

        {{-- ============================================================ Permission matrix --}}
        <div x-show="tab === 'matrix'" x-cloak role="tabpanel" x-data="permissionMatrix(@js($matrix))">
            <x-card flush
                    title="Role Permission Matrix"
                    subtitle="All 25 sub-modules grouped by module. V = View, C = Create, E = Edit, D = Delete.">
                <x-slot:actions>
                    <x-btn size="sm" icon="refresh-cw">Reset to defaults</x-btn>
                </x-slot:actions>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] border-collapse text-sm">
                        <caption class="sr-only">
                            Permission matrix: rows are sub-modules grouped by module, columns are roles,
                            each cell holds view, create, edit, and delete checkboxes.
                        </caption>

                        <thead>
                            <tr class="bg-neutral-100">
                                <th scope="col" class="sticky left-0 z-10 bg-neutral-100 px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-600">
                                    Module / Sub-module
                                </th>
                                @foreach($roles as $role)
                                    <th scope="col" class="border-l border-neutral-200 px-3 py-2 text-center">
                                        <span class="block text-xs font-semibold text-neutral-700">{{ $role }}</span>
                                        <label class="mt-1 inline-flex items-center gap-1.5 text-[11px] font-medium text-neutral-500">
                                            <input type="checkbox"
                                                   @change="toggleColumn(@js($role), $event.target.checked)"
                                                   :checked="columnAllOn(@js($role))"
                                                   class="h-3.5 w-3.5 rounded-[3px] border-neutral-300 text-primary-600">
                                            Select all
                                        </label>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($modules as $module)
                                {{-- Module group header --}}
                                <tr class="bg-primary-50">
                                    <th scope="colgroup" colspan="{{ count($roles) + 1 }}"
                                        class="sticky left-0 px-3 py-2 text-left">
                                        <span class="inline-flex items-center gap-2 text-[13px] font-semibold text-primary-800">
                                            <x-icon :name="$module['icon']" class="h-4 w-4" />
                                            {{ $module['label'] }}
                                        </span>
                                    </th>
                                </tr>

                                @foreach($module['items'] as $item)
                                    @php $rowKey = $item['route']; @endphp
                                    <tr class="border-t border-neutral-200 hover:bg-neutral-50">
                                        <th scope="row" class="sticky left-0 z-10 bg-white px-3 py-2.5 text-left font-normal">
                                            <span class="block pl-6 text-[13px] text-neutral-700">{{ $item['label'] }}</span>
                                            <label class="mt-0.5 inline-flex items-center gap-1.5 pl-6 text-[11px] text-neutral-500">
                                                <input type="checkbox"
                                                       @change="toggleRow(@js($rowKey), $event.target.checked)"
                                                       :checked="rowAllOn(@js($rowKey))"
                                                       class="h-3.5 w-3.5 rounded-[3px] border-neutral-300 text-primary-600">
                                                Select all
                                            </label>
                                        </th>

                                        @foreach($roles as $role)
                                            <td class="border-l border-neutral-200 px-3 py-2.5">
                                                <div class="flex items-center justify-center gap-2.5">
                                                    @foreach($permissions as $perm)
                                                        <label class="flex flex-col items-center gap-1"
                                                               title="{{ ucfirst($perm) }} — {{ $item['label'] }} — {{ $role }}">
                                                            <span class="text-[10px] font-semibold text-neutral-400">{{ $permAbbr[$perm] }}</span>
                                                            <input type="checkbox"
                                                                   x-model="grid[@js($rowKey)][@js($role)][@js($perm)]"
                                                                   class="h-4 w-4 rounded-[3px] border-neutral-300 text-primary-600"
                                                                   aria-label="{{ ucfirst($perm) }} {{ $item['label'] }} as {{ $role }}">
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-slot:footer>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-[13px] text-neutral-500">
                            Changes take effect the next time an affected user loads a page.
                        </p>
                        <div class="flex items-center gap-2">
                            <x-btn>Discard changes</x-btn>
                            <x-btn variant="primary" icon="check">Save Permissions</x-btn>
                        </div>
                    </div>
                </x-slot:footer>
            </x-card>
        </div>
    </x-tabs>

    {{-- ================================================================ user form --}}
    <x-drawer name="user-form" title="Edit user" subtitle="Teresita G. Gonzales · USR-001" width="max-w-xl">
        <form class="space-y-5" onsubmit="return false;">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form-field label="Full name" name="name" value="Teresita G. Gonzales" required />
                <x-form-field label="Employee ID" name="employee_id" value="EMP-00114" required tabular />
                <x-form-field class="sm:col-span-2" label="Email address" type="email" name="email"
                              value="tgonzales@ledger.coop.ph" required
                              help="Used as the sign-in username." />
                <x-form-field label="Role" type="select" name="role" required :options="$roles" value="Branch Manager" />
                <x-form-field label="Branch" type="select" name="branch" required
                              :options="array_merge(['All Branches'], collect($branches)->pluck('name')->all())"
                              value="Malolos Main Branch" />
                <x-form-field label="Mobile number" name="mobile" value="0917 664 0092" tabular />
                <x-form-field label="Status" type="select" name="status" :options="['Active', 'Inactive']" value="Active" />
            </div>

            <fieldset class="border-t border-neutral-200 pt-5">
                <legend class="mb-3 text-[13px] font-semibold uppercase tracking-wide text-neutral-500">Security</legend>
                <div class="space-y-3">
                    <label class="flex items-start gap-2.5">
                        <input type="checkbox" checked class="mt-0.5 h-4 w-4 rounded-[4px] border-neutral-300 text-primary-600">
                        <span class="text-[13px] text-neutral-700">Require password change at next sign-in</span>
                    </label>
                    <label class="flex items-start gap-2.5">
                        <input type="checkbox" class="mt-0.5 h-4 w-4 rounded-[4px] border-neutral-300 text-primary-600">
                        <span class="text-[13px] text-neutral-700">Require two-factor authentication</span>
                    </label>
                    <label class="flex items-start gap-2.5">
                        <input type="checkbox" checked class="mt-0.5 h-4 w-4 rounded-[4px] border-neutral-300 text-primary-600">
                        <span class="text-[13px] text-neutral-700">Restrict sign-in to branch IP addresses</span>
                    </label>
                </div>
            </fieldset>
        </form>

        <x-slot:footer>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <x-btn variant="danger-outline" icon="lock" @click="$dispatch('open-modal', 'deactivate-user')">Deactivate</x-btn>
                <div class="flex items-center gap-2">
                    <x-btn @click="$dispatch('close-drawer')">Cancel</x-btn>
                    <x-btn variant="primary" icon="check">Save User</x-btn>
                </div>
            </div>
        </x-slot:footer>
    </x-drawer>

    <x-modal name="deactivate-user" title="Deactivate this user?" tone="danger" icon="alert-triangle"
             subtitle="They lose access immediately; their records and audit trail stay intact.">
        <p class="text-sm text-neutral-600">
            Any accounts, centers, or pending approvals assigned to this user must be reassigned before deactivation,
            otherwise they will be left unowned.
        </p>
        <x-form-field class="mt-4" label="Reassign their work to" type="select" name="reassign_to" required
                      :options="collect($users)->pluck('name')->all()" />
        <x-form-field class="mt-4" label="Reason" type="textarea" name="deactivate_reason" rows="2" required
                      placeholder="e.g. Resigned effective 30 Sep 2026" />

        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="danger" icon="lock">Deactivate User</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
