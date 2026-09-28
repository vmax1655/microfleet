@php
    use App\Support\Format;

    $active = collect($products)->where('status', 'Active');
    $edit = $products[0];
@endphp

<div>
    <x-breadcrumb />

    <x-page-header
        title="Loan Products"
        subtitle="Define the interest method, term, and fee structure available to members.">
        <x-slot:actions>
            <x-btn icon="download">Export</x-btn>
            <x-btn variant="primary" icon="plus" @click="$dispatch('open-drawer', 'product-form')">New Product</x-btn>
        </x-slot:actions>
    </x-page-header>

    <section aria-label="Product summary" class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Active Products" :value="$active->count()" :delta="0.0" icon="briefcase" />
        <x-stat-card label="Loans on Book" :value="number_format($active->sum('active_loans'))" :delta="3.9" icon="file-text" />
        <x-stat-card label="Product Portfolio" :value="Format::pesoCompact($active->sum('portfolio'))" :delta="4.7" icon="wallet" />
        <x-stat-card label="Weighted Avg. Rate" value="2.2%" :delta="0.1" good-direction="down" icon="percent" />
    </section>

    <x-filter-bar search-label="Search products" search-placeholder="Product name or code…" :export="false">
        <x-select label="Interest method" :options="['Flat', 'Diminishing']" placeholder="Any method" width="w-40" />
        <x-select label="Frequency" :options="['Daily', 'Weekly', 'Semi-monthly', 'Monthly']" placeholder="Any frequency" width="w-44" />
        <x-select label="Status" :options="['Active', 'Draft', 'Archived']" placeholder="All statuses" width="w-36" />
    </x-filter-bar>

    @if(count($products) === 0)
        <x-card>
            <x-empty-state
                icon="briefcase"
                heading="No loan products defined"
                help="A loan product sets the interest method, term range, and fees that apply to every loan issued under it."
                action-label="New Product" />
        </x-card>
    @else
        <ul class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($products as $p)
                <li>
                    <article class="flex h-full flex-col rounded-[12px] border border-neutral-200 bg-white p-5 shadow-card">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="truncate text-[15px] font-semibold text-neutral-800">{{ $p['name'] }}</h2>
                                <p class="mt-0.5 text-xs tabular-nums text-neutral-500">{{ $p['code'] }} · {{ $p['id'] }}</p>
                            </div>
                            <x-status-badge :status="$p['status']" />
                        </div>

                        <p class="mt-3 text-[13px] leading-relaxed text-neutral-600">{{ $p['description'] }}</p>

                        <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3.5 border-t border-neutral-200 pt-4">
                            <x-kpi label="Interest" :value="$p['rate'].'% / mo'" :hint="$p['method'].' method'" />
                            <x-kpi label="Frequency" :value="$p['frequency']" :mono="false" />
                            <x-kpi label="Term range" :value="$p['term_min'].'–'.$p['term_max'].' months'" />
                            <x-kpi label="Amount range"
                                   :value="Format::pesoCompact($p['amount_min']).' – '.Format::pesoCompact($p['amount_max'])" />
                            <x-kpi label="Penalty rate" :value="$p['penalty_rate'].'% / mo'" />
                            <x-kpi label="Processing fee" :value="$p['processing_fee'].'%'" :hint="$p['grace_days'].'-day grace'" />
                        </dl>

                        <div class="mt-4 flex items-end justify-between gap-3 border-t border-neutral-200 pt-4">
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-neutral-500">Active loans</p>
                                <p class="mt-1 text-lg font-semibold tabular-nums text-neutral-900">{{ number_format($p['active_loans']) }}</p>
                                <p class="text-xs tabular-nums text-neutral-500">{{ Format::pesoCompact($p['portfolio']) }} outstanding</p>
                            </div>
                            <div class="flex gap-2">
                                <x-btn size="sm" icon="pencil" @click="$dispatch('open-drawer', 'product-form')">Edit</x-btn>
                            </div>
                        </div>
                    </article>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- ================================================================ create / edit form --}}
    <x-drawer name="product-form" title="Edit loan product" :subtitle="$edit['name'].' · '.$edit['code']" width="max-w-2xl">
        <form class="space-y-6" onsubmit="return false;">

            <fieldset>
                <legend class="mb-3 text-[13px] font-semibold uppercase tracking-wide text-neutral-500">Identity</legend>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form-field label="Product name" name="name" :value="$edit['name']" required />
                    <x-form-field label="Product code" name="code" :value="$edit['code']" required tabular
                                  help="Used as the prefix on loan IDs." />
                    <x-form-field class="sm:col-span-2" label="Description" type="textarea" name="description"
                                  rows="2" :value="$edit['description']" />
                </div>
            </fieldset>

            <fieldset>
                <legend class="mb-3 text-[13px] font-semibold uppercase tracking-wide text-neutral-500">Interest & fees</legend>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form-field label="Interest method" type="select" name="method" required
                                  :options="['Flat', 'Diminishing']" :value="$edit['method']"
                                  help="Flat charges interest on the original principal; diminishing charges on the outstanding balance." />
                    <x-form-field label="Interest rate" name="rate" suffix="%" :value="$edit['rate']" required tabular
                                  help="Per month." />
                    <x-form-field label="Penalty rate" name="penalty_rate" suffix="%" :value="$edit['penalty_rate']" required tabular />
                    <x-form-field label="Processing fee" name="processing_fee" suffix="%" :value="$edit['processing_fee']" required tabular
                                  help="Deducted from the proceeds at release." />
                </div>
            </fieldset>

            <fieldset>
                <legend class="mb-3 text-[13px] font-semibold uppercase tracking-wide text-neutral-500">Terms</legend>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form-field label="Payment frequency" type="select" name="frequency" required
                                  :options="['Daily', 'Weekly', 'Semi-monthly', 'Monthly']" :value="$edit['frequency']" />
                    <x-form-field label="Grace period" name="grace_days" suffix="days" :value="$edit['grace_days']" required tabular
                                  help="Days after the due date before a penalty accrues." />
                    <x-form-field label="Minimum term" name="term_min" suffix="mo" :value="$edit['term_min']" required tabular />
                    <x-form-field label="Maximum term" name="term_max" suffix="mo" :value="$edit['term_max']" required tabular />
                    <x-form-field label="Minimum amount" name="amount_min" prefix="₱"
                                  :value="number_format($edit['amount_min'], 2)" required tabular />
                    <x-form-field label="Maximum amount" name="amount_max" prefix="₱"
                                  :value="number_format($edit['amount_max'], 2)" required tabular
                                  error="Maximum amount must not exceed the ₱150,000.00 single-borrower ceiling." />
                </div>
            </fieldset>

            <fieldset>
                <legend class="mb-3 text-[13px] font-semibold uppercase tracking-wide text-neutral-500">Availability</legend>
                <div class="space-y-3">
                    @foreach([
                        ['Requires a co-maker', true],
                        ['Requires collateral above ₱50,000.00', true],
                        ['Available to first-cycle borrowers', false],
                        ['Group guarantee accepted in place of collateral', true],
                    ] as $index => [$rule, $on])
                        <label class="flex items-start gap-2.5">
                            <input type="checkbox" @checked($on)
                                   class="mt-0.5 h-4 w-4 rounded-[4px] border-neutral-300 text-primary-600">
                            <span class="text-[13px] text-neutral-700">{{ $rule }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        </form>

        <x-slot:footer>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <x-btn variant="danger-outline" icon="folder-open" @click="$dispatch('open-modal', 'archive-product')">Archive</x-btn>
                <div class="flex items-center gap-2">
                    <x-btn @click="$dispatch('close-drawer')">Cancel</x-btn>
                    <x-btn variant="primary" icon="check">Save Product</x-btn>
                </div>
            </div>
        </x-slot:footer>
    </x-drawer>

    <x-modal name="archive-product" title="Archive this product?" tone="danger" icon="alert-triangle"
             subtitle="Existing loans keep running; no new loans can be issued under it.">
        <p class="text-sm text-neutral-600">
            <span class="font-medium text-neutral-800">{{ $edit['name'] }}</span> currently has
            <span class="font-medium tabular-nums text-neutral-800">{{ number_format($edit['active_loans']) }}</span>
            active loans worth <span class="font-medium tabular-nums text-neutral-800">{{ Format::peso($edit['portfolio']) }}</span>.
            Those accounts continue on their original terms.
        </p>
        <x-form-field class="mt-4" label="Reason" type="textarea" name="archive_reason" rows="2" required
                      placeholder="e.g. Replaced by Negosyo Plus effective October 2026" />

        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="danger" icon="folder-open">Archive Product</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
