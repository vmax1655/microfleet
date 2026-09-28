@php
    use App\Support\Format;

    $grandDue = collect($groups)->sum('due_total');
@endphp

{{--
    The loan officer's field view. Rows are server-rendered so the sheet is
    printable and readable without JS; the inline inputs bind into the Alpine
    `collectionSheet` store, which keeps subtotals, the grand total, and the
    sticky footer in sync as amounts are typed.
--}}
<div x-data="collectionSheet(@js($payload))" class="pb-24">

    <div class="no-print">
        <x-breadcrumb />

        <x-page-header
            title="Daily Collection Sheet"
            :subtitle="'Collections for '.Format::date($date).' · Malolos Main Branch'">
            <x-slot:actions>
                <x-btn icon="printer" onclick="window.print()">Print sheet</x-btn>
                <x-btn icon="download">Export</x-btn>
            </x-slot:actions>
        </x-page-header>

        {{-- Date + center selector --}}
        <section class="mb-4 rounded-[12px] border border-neutral-200 bg-white p-4 shadow-card">
            <form class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end" onsubmit="return false;">
                <div>
                    <label for="sheet-date" class="mb-1 block text-xs font-medium text-neutral-600">Collection date</label>
                    <input id="sheet-date" type="date" value="{{ $date }}"
                           class="h-10 w-full rounded-[8px] border border-neutral-300 bg-white px-3 text-sm tabular-nums text-neutral-800 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-600/30 sm:w-44">
                </div>

                <div class="min-w-0 flex-1 sm:max-w-xs">
                    <label for="sheet-center" class="mb-1 block text-xs font-medium text-neutral-600">Center</label>
                    <select id="sheet-center"
                            class="h-10 w-full rounded-[8px] border border-neutral-300 bg-white px-3 text-sm text-neutral-800 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-600/30">
                        <option value="">All centers meeting today ({{ count($groups) }})</option>
                        @foreach($centers as $c)
                            <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <x-btn size="lg" icon="refresh-cw" class="flex-1 sm:flex-none">Reload sheet</x-btn>
                </div>
            </form>
        </section>
    </div>

    {{-- Print-only header --}}
    <div class="mb-4 hidden print:block">
        <h1 class="text-xl font-semibold text-neutral-900">Daily Collection Sheet — {{ Format::date($date) }}</h1>
        <p class="text-sm text-neutral-600">Malolos Main Branch · Ledger Microfinance Cooperative</p>
    </div>

    {{-- ================================================================ groups --}}
    <div class="space-y-5">
        @foreach($groups as $gi => $group)
            <section class="overflow-hidden rounded-[12px] border border-neutral-200 bg-white shadow-card print-plain"
                     aria-label="{{ $group['center']['name'] }}">

                <header class="flex flex-wrap items-start justify-between gap-3 border-b border-neutral-200 bg-neutral-50 px-4 py-3">
                    <div class="min-w-0">
                        <h2 class="truncate text-[15px] font-semibold text-neutral-800">{{ $group['center']['name'] }}</h2>
                        <p class="mt-0.5 text-xs text-neutral-500">
                            {{ $group['center']['meeting_day'] }} · {{ $group['center']['meeting_time'] }} ·
                            {{ $group['center']['officer'] }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-medium uppercase tracking-wide text-neutral-500">Due today</p>
                        <p class="text-[15px] font-semibold tabular-nums text-neutral-900">{{ Format::peso($group['due_total']) }}</p>
                    </div>
                </header>

                <table class="stack-table w-full border-collapse text-sm">
                    <caption class="sr-only">Collection sheet for {{ $group['center']['name'] }}</caption>
                    <thead class="bg-neutral-100">
                        <tr>
                            <th scope="col" class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-neutral-600">Member</th>
                            <th scope="col" class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-neutral-600">Loan ID</th>
                            <th scope="col" class="px-3 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-neutral-600">Amount Due</th>
                            <th scope="col" class="px-3 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-neutral-600">Amount Collected</th>
                            <th scope="col" class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-neutral-600">Method</th>
                            <th scope="col" class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-neutral-600">Status</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-neutral-200">
                        @foreach($group['rows'] as $ri => $row)
                            <tr class="transition-colors hover:bg-primary-50 {{ $ri % 2 ? 'bg-neutral-50' : '' }}">

                                <td data-label="Member" class="px-3 py-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <x-avatar :name="$row['member']" size="sm" class="print:hidden" />
                                        <div class="min-w-0 text-left">
                                            <a href="{{ route('membership.profile', $row['member_id']) }}"
                                               class="block truncate font-medium text-neutral-800 hover:text-primary-700 hover:underline">{{ $row['member'] }}</a>
                                            <span class="block text-xs tabular-nums text-neutral-500">{{ $row['member_id'] }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td data-label="Loan ID" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ $row['loan_id'] }}</td>

                                <td data-label="Amount Due" class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800">
                                    {{ Format::peso($row['due']) }}
                                </td>

                                <td data-label="Amount Collected" class="px-3 py-2.5">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="relative w-36">
                                            <label for="collect-{{ $gi }}-{{ $ri }}" class="sr-only">
                                                Amount collected from {{ $row['member'] }}
                                            </label>
                                            <span class="pointer-events-none absolute inset-y-0 left-0 grid w-7 place-items-center text-sm text-neutral-500">₱</span>
                                            <input id="collect-{{ $gi }}-{{ $ri }}"
                                                   type="number"
                                                   inputmode="decimal"
                                                   step="0.01"
                                                   min="0"
                                                   x-model.number="groups[{{ $gi }}].rows[{{ $ri }}].collected"
                                                   value="{{ $row['collected'] }}"
                                                   class="h-10 w-full rounded-[8px] border border-neutral-300 bg-white pl-7 pr-2 text-right text-sm tabular-nums text-neutral-800 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-600/30">
                                        </div>
                                        <button type="button"
                                                @click="markFullyPaid(groups[{{ $gi }}].rows[{{ $ri }}])"
                                                class="shrink-0 rounded-[8px] border border-neutral-300 p-2 text-neutral-500 transition-colors hover:bg-neutral-50 hover:text-primary-700 print:hidden"
                                                aria-label="Mark {{ $row['member'] }} as fully paid">
                                            <x-icon name="check" />
                                        </button>
                                    </div>
                                </td>

                                <td data-label="Method" class="px-3 py-2.5">
                                    <label for="method-{{ $gi }}-{{ $ri }}" class="sr-only">Payment method for {{ $row['member'] }}</label>
                                    <select id="method-{{ $gi }}-{{ $ri }}"
                                            x-model="groups[{{ $gi }}].rows[{{ $ri }}].method"
                                            class="h-10 w-full rounded-[8px] border border-neutral-300 bg-white px-2 text-sm text-neutral-800 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-600/30 sm:w-28">
                                        <option>Cash</option>
                                        <option>GCash</option>
                                        <option>Bank Transfer</option>
                                    </select>
                                </td>

                                <td data-label="Status" class="px-3 py-2.5">
                                    <template x-if="rowStatus(groups[{{ $gi }}].rows[{{ $ri }}]) === 'Paid'">
                                        <span class="inline-flex items-center rounded-full border border-[#A6E0C1] bg-[#E7F6EE] px-2.5 py-0.5 text-xs font-medium text-[#14804A]">Paid</span>
                                    </template>
                                    <template x-if="rowStatus(groups[{{ $gi }}].rows[{{ $ri }}]) === 'Partial'">
                                        <span class="inline-flex items-center rounded-full border border-[#F5D28A] bg-[#FEF0D6] px-2.5 py-0.5 text-xs font-medium text-[#B54708]">Partial</span>
                                    </template>
                                    <template x-if="rowStatus(groups[{{ $gi }}].rows[{{ $ri }}]) === 'Pending'">
                                        <span class="inline-flex items-center rounded-full border border-[#C9D2CD] bg-[#F0F4F2] px-2.5 py-0.5 text-xs font-medium text-[#5A6B64]">Pending</span>
                                    </template>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    {{-- Per-group subtotal --}}
                    <tfoot class="border-t-2 border-neutral-300 bg-neutral-50">
                        <tr>
                            <td data-label="" class="px-3 py-3 text-left text-[13px] font-semibold text-neutral-700" colspan="2">
                                Subtotal — {{ count($group['rows']) }} members
                            </td>
                            <td data-label="Due" class="px-3 py-3 text-right font-semibold tabular-nums text-neutral-800">
                                {{ Format::peso($group['due_total']) }}
                            </td>
                            <td data-label="Collected" class="px-3 py-3 text-right font-semibold tabular-nums text-primary-700"
                                x-text="peso(groupCollected(groups[{{ $gi }}]))">
                                {{ Format::peso($group['collected_total']) }}
                            </td>
                            <td data-label="" class="px-3 py-3" colspan="2">
                                <span class="text-[13px] tabular-nums text-neutral-500">
                                    Variance
                                    <span class="font-medium"
                                          :class="groupCollected(groups[{{ $gi }}]) - groupDue(groups[{{ $gi }}]) < 0 ? 'text-danger' : 'text-success'"
                                          x-text="peso(groupCollected(groups[{{ $gi }}]) - groupDue(groups[{{ $gi }}]))"></span>
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </section>
        @endforeach

        @if(count($groups) === 0)
            <x-card>
                <x-empty-state
                    icon="clipboard-list"
                    heading="No centers meet on this date"
                    help="Pick another date, or check the center schedule under Groups & Centers."
                    action-label="View centers"
                    :action-href="route('membership.groups')"
                    action-icon="map-pin" />
            </x-card>
        @endif
    </div>

    {{-- Grand total --}}
    <x-card class="mt-5 print-plain">
        <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <x-kpi label="Total due" :value="Format::peso($grandDue)" />
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-neutral-500">Total collected</dt>
                <dd class="mt-1 text-[15px] font-semibold tabular-nums text-primary-700" x-text="peso(grandCollected())">₱0.00</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-neutral-500">Variance</dt>
                <dd class="mt-1 text-[15px] font-semibold tabular-nums"
                    :class="grandCollected() - grandDue() < 0 ? 'text-danger' : 'text-success'"
                    x-text="peso(grandCollected() - grandDue())">₱0.00</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-neutral-500">Members paid</dt>
                <dd class="mt-1 text-[15px] font-semibold tabular-nums text-neutral-900">
                    <span x-text="paidCount()">0</span> of <span x-text="totalCount()">0</span>
                </dd>
            </div>
        </dl>
    </x-card>

    {{-- ================================================================ sticky footer --}}
    <div class="no-print sticky bottom-0 z-20 -mx-6 mt-5 border-t border-neutral-200 bg-white/95 px-6 py-3 backdrop-blur supports-[backdrop-filter]:bg-white/85">
        <div class="mx-auto flex max-w-content flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-baseline gap-x-5 gap-y-1">
                <p class="text-[13px] text-neutral-600">
                    Total collected
                    <span class="ml-1.5 text-lg font-semibold tabular-nums text-neutral-900" x-text="peso(grandCollected())">₱0.00</span>
                </p>
                <p class="text-[13px] tabular-nums text-neutral-500">
                    <span x-text="paidCount()">0</span> of <span x-text="totalCount()">0</span> members ·
                    <span x-text="peso(grandDue())">₱0.00</span> due
                </p>
            </div>

            <div class="flex w-full items-center gap-2 sm:w-auto">
                <x-btn size="lg" class="flex-1 sm:flex-none" icon="folder-open">Save draft</x-btn>
                <x-btn size="lg" variant="primary" class="flex-1 sm:flex-none" icon="check"
                       @click="$dispatch('open-modal', 'post-collections')">Post Collections</x-btn>
            </div>
        </div>
    </div>

    {{-- ================================================================ confirmation --}}
    <x-modal name="post-collections" title="Post these collections?" icon="banknote" tone="warning"
             subtitle="Receipts are issued and each loan ledger is updated immediately.">
        <dl class="grid grid-cols-2 gap-4 rounded-[12px] border border-neutral-200 bg-neutral-50 p-4">
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-neutral-500">Receipts to issue</dt>
                <dd class="mt-1 text-lg font-semibold tabular-nums text-neutral-900" x-text="paidCount()">0</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-neutral-500">Batch total</dt>
                <dd class="mt-1 text-lg font-semibold tabular-nums text-neutral-900" x-text="peso(grandCollected())">₱0.00</dd>
            </div>
        </dl>

        <x-form-field class="mt-4" label="Starting OR number" name="or_start" value="OR-2026-0084531" required tabular
                      help="Receipts are numbered consecutively from here." />

        <div class="mt-4 flex items-start gap-2.5 rounded-[8px] border border-[#F5D28A] bg-[#FEF0D6] p-3.5">
            <x-icon name="alert-triangle" class="mt-0.5 shrink-0 text-warning" />
            <p class="text-[13px] text-warning">
                Posting is final. Members with a blank amount are recorded as non-payers for the day and will begin
                accruing penalties after the product grace period.
            </p>
        </div>

        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="primary" icon="check">Post Collections</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
