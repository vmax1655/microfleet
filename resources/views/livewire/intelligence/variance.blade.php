@php
    use App\Support\Format;
@endphp

<div>
    <x-breadcrumb />

    {{-- Dismissible Alert Banner --}}
    @if($bannerMessage)
        <div x-data="{ show: true }"
             x-show="show"
             x-init="setTimeout(() => show = false, 7000)"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="mb-4 flex items-center justify-between rounded-[8px] border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm"
             role="alert">
            <div class="flex items-center gap-2">
                <x-icon name="check-circle" class="h-5 w-5 shrink-0 text-emerald-600" />
                <span class="font-medium">{{ $bannerMessage }}</span>
            </div>
            <button @click="show = false" class="text-emerald-600 hover:text-emerald-900">
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>
    @endif

    <x-page-header title="Prediction vs Actual" subtitle="Compare ML fuel and cost predictions against recorded field outcomes.">
        <x-slot:actions>
            <x-btn icon="download" wire:click="exportCsv">Export</x-btn>
            @if($canManageVariance)
                <x-btn variant="primary" icon="sparkles"
                    wire:click="openReconcileModal"
                    @click="$dispatch('open-modal', 'reconcile-variance')">Reconcile</x-btn>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-card class="mt-6" flush>
        <x-slot:title>Variance Review</x-slot:title>
        <x-slot:subtitle>Trips beyond variance thresholds are sent to management review and feedback capture.</x-slot:subtitle>

        <x-data-table sort-key="trip" caption="Prediction versus actual variance">
            <x-slot:head>
                <x-th sort="trip">Trip / Reservation</x-th>
                <x-th sort="route">Route</x-th>
                <x-th sort="predicted">Predicted Fuel</x-th>
                <x-th sort="actual">Actual Fuel</x-th>
                <x-th sort="variance">Variance</x-th>
                <x-th sort="status">Status</x-th>
                <x-th align="right" sr-only>Actions</x-th>
            </x-slot:head>
            @forelse($rows as $i => $row)
                <tr data-row data-trip="{{ $row['trip'] }}" data-route="{{ $row['route'] }}" data-predicted="{{ $row['predictedFuel'] }}" data-actual="{{ $row['actualFuel'] }}" data-variance="{{ $row['variance'] }}" data-status="{{ $row['status'] }}"
                    class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">
                    <td class="px-3 py-2.5 font-medium tabular-nums text-neutral-800" data-label="Trip / Reservation">
                        {{ $row['trip'] }}
                        @if($row['prediction']->trip?->vehicle)
                            <span class="block text-[11px] text-neutral-400">
                                {{ $row['prediction']->trip->vehicle->plate_number }} · {{ $row['prediction']->trip->driver?->user?->name ?? 'No Driver' }}
                            </span>
                        @endif
                    </td>
                    <td class="px-3 py-2.5 tabular-nums text-neutral-600" data-label="Route">{{ $row['route'] }}</td>
                    <td class="px-3 py-2.5 tabular-nums text-neutral-600" data-label="Predicted Fuel">
                        {{ number_format($row['predictedFuel'], 2) }} L
                        <span class="block text-[11px] text-neutral-400">{{ Format::peso($row['predictedCost']) }}</span>
                    </td>
                    <td class="px-3 py-2.5 tabular-nums text-neutral-600" data-label="Actual Fuel">
                        {{ number_format($row['actualFuel'], 2) }} L
                        <span class="block text-[11px] text-neutral-400">{{ Format::peso($row['actualCost']) }}</span>
                    </td>
                    <td class="px-3 py-2.5 font-medium tabular-nums {{ abs($row['variance']) >= 20 ? 'text-danger' : 'text-warning' }}" data-label="Variance">
                        {{ ($row['variance'] > 0 ? '+' : '') . number_format($row['variance'], 1) }}%
                    </td>
                    <td class="px-3 py-2.5" data-label="Status"><x-status-badge :status="$row['status']" /></td>
                    <td class="px-3 py-2.5 text-right space-x-1 whitespace-nowrap" data-label="">
                        @if($canManageVariance)
                            <x-btn size="xs" variant="ghost" icon="refresh-cw"
                                wire:click="reconcileSingle('{{ $row['trip'] }}')"
                                title="Re-sync this trip">
                                Sync
                            </x-btn>
                            <x-btn size="xs" variant="secondary" icon="eye"
                                wire:click="openReviewModal({{ $row['prediction']->id }})">
                                Review
                            </x-btn>
                        @else
                            <span class="text-sm text-neutral-400">Read only</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-3 py-8 text-center text-sm text-neutral-500">
                        No reconciled predictions yet. Click <strong>Reconcile</strong> to compare field outcomes against predictions.
                    </td>
                </tr>
            @endforelse
        </x-data-table>
    </x-card>

    {{-- ── Reconcile Modal ── --}}
    @if($canManageVariance)
        <x-modal name="reconcile-variance" title="Reconcile Prediction Variance" subtitle="Reconcile actual fuel receipts and trip costs against initial ML forecasts." icon="sparkles" size="md">
            <div class="space-y-4">
                <x-form-field label="Select Trip to Reconcile" type="select" name="trip"
                    wire:model="trip"
                    :options="array_merge(['all' => '— All Completed Trips —'], $tripOptions)"
                    :error="$errors->first('trip')"
                    required />

                <div class="rounded-[8px] border border-neutral-200 bg-neutral-50 p-3 text-xs text-neutral-600 space-y-1">
                    <p class="font-semibold text-neutral-800">What does reconciliation do?</p>
                    <ul class="list-disc pl-4 space-y-0.5">
                        <li>Pulls logged fuel volume from receipts for the selected trip.</li>
                        <li>Retrieves actual odometer distance and cost rollups.</li>
                        <li>Calculates variance percentage between ML forecast and real usage.</li>
                        <li>Flags trips with variance &gt; 20% for management review.</li>
                    </ul>
                </div>
            </div>

            <x-slot:footer>
                <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
                <x-btn variant="primary" icon="refresh-cw"
                    wire:click="reconcileVariance"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="reconcileVariance">Run Reconciliation</span>
                    <span wire:loading wire:target="reconcileVariance">Reconciling...</span>
                </x-btn>
            </x-slot:footer>
        </x-modal>

        {{-- ── Variance Feedback Modal ── --}}
        <x-modal name="variance-detail" title="Variance Review & Feedback" subtitle="Capture model feedback for future hyperparameter tuning." icon="alert-triangle" tone="warning" size="lg">
            <p class="text-sm text-neutral-600 mb-4">
                Trips beyond the threshold can be annotated with real-world reasons (route detours, receipt typo, driver behavior) to retrain the ML model.
            </p>

            <form id="variance-feedback-form" wire:submit.prevent="saveFeedback" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form-field label="Prediction Reference" type="select" name="prediction"
                        wire:model="prediction"
                        :options="$predictionOptions ?: ['' => '— Run reconciliation first —']"
                        :error="$errors->first('prediction')"
                        required />

                    <x-form-field label="Investigation Outcome" type="select" name="outcome"
                        wire:model="outcome"
                        :options="['Valid anomaly' => 'Valid anomaly', 'Receipt issue' => 'Receipt issue', 'Route deviation' => 'Route deviation', 'Model overestimated' => 'Model overestimated', 'Model underestimated' => 'Model underestimated']"
                        :error="$errors->first('outcome')"
                        required />

                    <x-form-field label="Feedback Category" type="select" name="feedback_type"
                        wire:model="feedback_type"
                        :options="['variance_review' => 'Variance Review', 'fuel_anomaly' => 'Fuel Anomaly', 'route_context' => 'Route Context']"
                        :error="$errors->first('feedback_type')"
                        required />
                </div>

                <x-form-field label="Reviewer Notes / Findings" type="textarea" name="notes"
                    wire:model="notes"
                    placeholder="Enter details on why variance occurred..."
                    :error="$errors->first('notes')"
                    rows="3" />
            </form>

            <x-slot:footer>
                <x-btn @click="$dispatch('close-modal')">Close</x-btn>
                <x-btn variant="primary" icon="check"
                    wire:click="saveFeedback"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveFeedback">Save Feedback</span>
                    <span wire:loading wire:target="saveFeedback">Saving...</span>
                </x-btn>
            </x-slot:footer>
        </x-modal>
    @endif
</div>
