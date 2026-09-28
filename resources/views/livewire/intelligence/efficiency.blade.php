@php use App\Support\Format; @endphp

<div>
    <x-breadcrumb />

    {{-- Feedback Banner --}}
    @if($showBanner)
        <div class="{{ $bannerType === 'success' ? 'bg-green-50 border-green-300 text-green-800' : 'bg-red-50 border-red-300 text-red-800' }} border rounded-lg px-4 py-3 mb-4 flex items-start gap-3">
            <x-icon name="{{ $bannerType === 'success' ? 'check-circle' : 'x-circle' }}" class="w-5 h-5 shrink-0 mt-0.5" />
            <span class="text-sm flex-1">{{ $bannerMessage }}</span>
            <button wire:click="dismissBanner" class="ml-2 text-current opacity-60 hover:opacity-100">&times;</button>
        </div>
    @endif

    <x-page-header title="Fleet Efficiency Score" subtitle="Rule-based scorecard using fuel efficiency, cost per kilometer, alerts, and vehicle readiness.">
        <x-slot:actions>
            <x-btn icon="download" wire:click="exportCsv" wire:loading.attr="disabled">Export CSV</x-btn>
            @if($canRefreshScores)
                <x-btn variant="primary" icon="refresh-cw"
                    @click="$dispatch('open-modal', 'refresh-efficiency')">Refresh Scores</x-btn>
            @endif
        </x-slot:actions>
    </x-page-header>

    <section class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($riskCards as $card)
            <x-stat-card :label="$card['label']" :value="number_format($card['value'])" :icon="$card['icon']" />
        @endforeach
    </section>

    <x-card class="mt-5" flush>
        <x-slot:title>
            <div class="flex items-center justify-between w-full">
                <span>Efficiency Scorecard</span>
                @if($canRefreshScores)
                    <x-btn size="xs" icon="refresh-cw" wire:click="refreshScores" wire:loading.attr="disabled" wire:target="refreshScores">
                        <span wire:loading.remove wire:target="refreshScores">Quick Refresh</span>
                        <span wire:loading wire:target="refreshScores">Updating…</span>
                    </x-btn>
                @endif
            </div>
        </x-slot:title>
        <x-slot:subtitle>Scores combine trip performance, fuel use, cost behavior, and maintenance risk.</x-slot:subtitle>

        <x-data-table sort-key="score" sort-dir="desc" caption="Fleet efficiency scorecard">
            <x-slot:head>
                <x-th sort="vehicle">Vehicle</x-th>
                <x-th sort="driver">Recent Driver</x-th>
                <x-th sort="km_l" align="right">km/L</x-th>
                <x-th sort="ontime" align="right">On-time</x-th>
                <x-th sort="cost_km" align="right">Cost / km</x-th>
                <x-th sort="score" align="right">Score</x-th>
                <x-th sort="status">Status</x-th>
            </x-slot:head>
            @forelse($scores as $i => $row)
                <tr data-row data-vehicle="{{ $row['vehicle'] }}" data-driver="{{ $row['driver'] }}" data-km_l="{{ $row['km_l'] }}" data-ontime="{{ $row['ontime'] }}" data-cost_km="{{ $row['cost_km'] }}" data-score="{{ $row['score'] }}" data-status="{{ $row['status'] }}"
                    class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">
                    <td class="px-3 py-2.5 font-medium tabular-nums text-neutral-800" data-label="Vehicle">{{ $row['vehicle'] }}</td>
                    <td class="px-3 py-2.5 text-neutral-600" data-label="Recent Driver">{{ $row['driver'] }}</td>
                    <td class="px-3 py-2.5 text-right tabular-nums text-neutral-600" data-label="km/L">{{ number_format($row['km_l'], 1) }}</td>
                    <td class="px-3 py-2.5 text-right tabular-nums text-neutral-600" data-label="On-time">{{ number_format($row['ontime']) }}%</td>
                    <td class="px-3 py-2.5 text-right tabular-nums text-neutral-600" data-label="Cost / km">{{ Format::peso($row['cost_km']) }}</td>
                    <td class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800" data-label="Score">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $row['score'] >= 75 ? 'bg-emerald-100 text-emerald-800' : ($row['score'] >= 50 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                            {{ $row['score'] }}%
                        </span>
                    </td>
                    <td class="px-3 py-2.5" data-label="Status"><x-status-badge :status="$row['status']" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-3 py-8 text-center text-sm text-neutral-500">No vehicle data available.</td>
                </tr>
            @endforelse
        </x-data-table>
    </x-card>

    @if($canRefreshScores)
        <x-modal name="refresh-efficiency" title="Refresh Efficiency Scores" subtitle="Recompute scores from latest trip, fuel, cost, alert, and readiness data." icon="refresh-cw">
            <div class="space-y-3 text-sm text-neutral-600">
                <p>This rule-based engine recalculates fleet efficiency metrics based on live operational records:</p>
                <ul class="list-disc pl-5 space-y-1 text-xs text-neutral-500">
                    <li><strong>Fuel Efficiency (km/L):</strong> Aggregated distance divided by total fuel consumption.</li>
                    <li><strong>Cost per Kilometer:</strong> Sum of reconciled fuel, approved expenses, and maintenance rates divided by distance.</li>
                    <li><strong>Alert Penalties:</strong> Deductions for open or monitoring maintenance risk alerts.</li>
                    <li><strong>Readiness Factor:</strong> Vehicle availability status in the fleet.</li>
                </ul>
            </div>
            <x-slot:footer>
                <x-btn @click="$dispatch('close-modal', 'refresh-efficiency')">Cancel</x-btn>
                <x-btn variant="primary" icon="refresh-cw"
                    wire:click="refreshScores"
                    wire:loading.attr="disabled"
                    wire:target="refreshScores">
                    <span wire:loading.remove wire:target="refreshScores">Recalculate Scores</span>
                    <span wire:loading wire:target="refreshScores">Recalculating…</span>
                </x-btn>
            </x-slot:footer>
        </x-modal>
    @endif
</div>
