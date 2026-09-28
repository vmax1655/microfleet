@php use App\Support\Format; @endphp

<div>
    <x-breadcrumb />

    {{-- Success / Error Banner --}}
    @if($showBanner)
        <div class="{{ $bannerType === 'success' ? 'bg-green-50 border-green-300 text-green-800' : 'bg-red-50 border-red-300 text-red-800' }} border rounded-lg px-4 py-3 mb-4 flex items-start gap-3">
            <x-icon name="{{ $bannerType === 'success' ? 'check-circle' : 'x-circle' }}" class="w-5 h-5 shrink-0 mt-0.5" />
            <span class="text-sm flex-1">{{ $bannerMessage }}</span>
            <button wire:click="dismissBanner" class="ml-2 text-current opacity-60 hover:opacity-100">&times;</button>
        </div>
    @endif

    <x-page-header title="ML Fuel Predictor" subtitle="Scikit-learn fuel and cost prediction with PHP rule-based fallback.">
        <x-slot:actions>
            <x-btn icon="download" wire:click="exportCsv" wire:loading.attr="disabled">Export CSV</x-btn>
            @if($canRunPrediction)
                <x-btn variant="primary" icon="cpu" @click="$dispatch('open-modal', 'run-prediction')">Run Prediction</x-btn>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Risk Stat Cards --}}
    <section class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($riskCards as $card)
            <x-stat-card :label="$card['label']" :value="number_format($card['value'])" :icon="$card['icon']" />
        @endforeach
    </section>

    {{-- Prediction Queue Table --}}
    <x-card class="mt-5" flush>
        <x-slot:title>Prediction Queue</x-slot:title>
        <x-slot:subtitle>Predictions are advisory and are reconciled against actual fuel and cost records.</x-slot:subtitle>

        <x-data-table sort-key="reservation" caption="ML fuel predictions">
            <x-slot:head>
                <x-th sort="reservation">Reservation / Trip</x-th>
                <x-th sort="route">Route</x-th>
                <x-th sort="model">Model</x-th>
                <x-th sort="liters" align="right">Predicted Liters</x-th>
                <x-th sort="cost" align="right">Predicted Cost</x-th>
                <x-th sort="actual_liters" align="right">Actual Liters</x-th>
                <x-th sort="actual_cost" align="right">Actual Cost</x-th>
                <x-th sort="variance" align="right">Variance</x-th>
                <x-th sort="status">Status</x-th>
            </x-slot:head>

            @forelse($predictions as $i => $row)
                @php
                    $reference = $row->reservation?->reservation_number ?? $row->trip?->trip_number ?? '-';
                    $route     = $row->reservation?->route?->route_code ?? $row->trip?->route?->route_code ?? '-';
                    $status    = $row->actual_cost ? 'Reconciled' : 'Generated';
                    $variance  = $row->variance_percent;
                    $channel   = $row->feature_payload['channel'] ?? $row->feature_payload['provider'] ?? null;
                    $modelLabel = $row->model_name.' '.$row->model_version;
                @endphp
                <tr data-row
                    data-reservation="{{ $reference }}"
                    data-route="{{ $route }}"
                    data-model="{{ $row->model_name }}"
                    data-liters="{{ $row->predicted_fuel_liters }}"
                    data-cost="{{ $row->predicted_cost }}"
                    data-actual_liters="{{ $row->actual_fuel_liters ?? 0 }}"
                    data-actual_cost="{{ $row->actual_cost ?? 0 }}"
                    data-variance="{{ $variance ?? 0 }}"
                    data-status="{{ $status }}"
                    class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">

                    <td class="px-3 py-2.5 font-medium tabular-nums text-neutral-800" data-label="Reservation / Trip">
                        {{ $reference }}
                    </td>
                    <td class="px-3 py-2.5 tabular-nums text-neutral-600" data-label="Route">{{ $route }}</td>
                    <td class="px-3 py-2.5 text-neutral-600 text-xs" data-label="Model">
                        <span class="font-medium">{{ $row->model_name }}</span>
                        <span class="text-neutral-400 ml-1">{{ $row->model_version }}</span>
                        @if($channel && str_contains($channel, 'fastapi'))
                            <span class="ml-1 inline-block bg-green-100 text-green-700 text-[10px] px-1.5 py-0.5 rounded">FastAPI</span>
                        @elseif($channel && str_contains($channel, 'python_cli'))
                            <span class="ml-1 inline-block bg-blue-100 text-blue-700 text-[10px] px-1.5 py-0.5 rounded">CLI</span>
                        @endif
                    </td>
                    <td class="px-3 py-2.5 text-right tabular-nums text-neutral-700" data-label="Predicted Liters">
                        {{ number_format((float) $row->predicted_fuel_liters, 2) }}L
                    </td>
                    <td class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800" data-label="Predicted Cost">
                        {{ Format::peso($row->predicted_cost) }}
                    </td>
                    <td class="px-3 py-2.5 text-right tabular-nums text-neutral-500" data-label="Actual Liters">
                        {{ $row->actual_fuel_liters !== null ? number_format((float) $row->actual_fuel_liters, 2).'L' : '—' }}
                    </td>
                    <td class="px-3 py-2.5 text-right tabular-nums text-neutral-500" data-label="Actual Cost">
                        {{ $row->actual_cost !== null ? Format::peso($row->actual_cost) : '—' }}
                    </td>
                    <td class="px-3 py-2.5 text-right tabular-nums" data-label="Variance">
                        @if($variance !== null)
                            <span class="{{ $variance > 20 || $variance < -20 ? 'text-red-600 font-semibold' : ($variance > 10 || $variance < -10 ? 'text-amber-600' : 'text-green-600') }}">
                                {{ $variance > 0 ? '+' : '' }}{{ number_format((float) $variance, 1) }}%
                            </span>
                        @else
                            <span class="text-neutral-300">—</span>
                        @endif
                    </td>
                    <td class="px-3 py-2.5" data-label="Status">
                        <x-status-badge :status="$status" />
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-3 py-8 text-center text-sm text-neutral-500">
                        No predictions generated yet. Click <strong>Run Prediction</strong> to generate one.
                    </td>
                </tr>
            @endforelse
        </x-data-table>
    </x-card>

    {{-- Run Prediction Modal --}}
    @if($canRunPrediction)
        <x-modal name="run-prediction" title="Run Fuel Prediction" subtitle="Generate estimated fuel and cost for a reservation using scikit-learn." icon="cpu">
            <form id="run-prediction-form" wire:submit.prevent="runPrediction">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-form-field
                            label="Reservation"
                            type="select"
                            name="reservation"
                            wire:model="reservation"
                            :options="collect($reservationOptions)->mapWithKeys(fn($v) => [$v => $v])->toArray()"
                            :error="$errors->first('reservation')"
                            required
                        />
                    </div>
                    <div class="sm:col-span-2">
                        <x-form-field
                            label="Fuel price / liter"
                            type="number"
                            name="fuel_price"
                            wire:model="fuel_price"
                            prefix="PHP"
                            step="0.01"
                            min="1"
                            max="999"
                            :error="$errors->first('fuel_price')"
                            tabular
                            required
                        />
                    </div>
                </div>

                <div class="mt-3 rounded-lg bg-blue-50 border border-blue-200 px-4 py-3 text-xs text-blue-700">
                    <strong>How it works:</strong> The system calls the scikit-learn RandomForest model (FastAPI → CLI fallback).
                    Features used: route distance, vehicle efficiency, load level, passenger count, road profile, fuel price.
                </div>
            </form>

            <x-slot:footer>
                <x-btn @click="$dispatch('close-modal', 'run-prediction')">Cancel</x-btn>
                <x-btn variant="primary" icon="cpu"
                    wire:click="runPrediction"
                    wire:loading.attr="disabled"
                    wire:target="runPrediction">
                    <span wire:loading.remove wire:target="runPrediction">Predict</span>
                    <span wire:loading wire:target="runPrediction">Predicting…</span>
                </x-btn>
            </x-slot:footer>
        </x-modal>
    @endif
</div>
