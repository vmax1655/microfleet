<?php

namespace App\Livewire\Logistics;

use App\Models\TransportCost;
use App\Models\Trip;
use App\Models\TripExpense;
use App\Support\Rbac;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Trip Expenses')]
class Expenses extends Component
{
    public string $trip = '';
    public string $expense_type = 'Toll';
    public string $receipt_number = '';
    public float|string $amount = '';
    public string $incurred_at = '';
    public string $description = '';
    public ?int $selectedExpenseId = null;

    public function mount(): void
    {
        $this->incurred_at = now()->toDateString();
        $this->trip = $this->tripOptions()->first() ?? '';
        $this->receipt_number = 'EXP-'.now()->format('Ymd-His');
    }

    public function submitExpense(): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'logistics.expenses', 'create'), 403);

        $data = $this->validate([
            'trip' => ['required', 'exists:trips,trip_number'],
            'expense_type' => ['required', 'string', 'max:80'],
            'receipt_number' => ['nullable', 'string', 'max:80'],
            'amount' => ['required', 'numeric', 'min:1'],
            'incurred_at' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $trip = Trip::with('driver.user')
            ->where('trip_number', $data['trip'])
            ->firstOrFail();

        abort_unless($this->canSubmitForTrip($trip), 403);

        TripExpense::create([
            'trip_id' => $trip->id,
            'expense_type' => str($data['expense_type'])->snake()->toString(),
            'amount' => $data['amount'],
            'receipt_number' => $data['receipt_number'],
            'description' => $data['description'],
            'approval_status' => 'pending',
            'incurred_at' => $data['incurred_at'],
        ]);

        $this->reset('amount', 'description');
        $this->receipt_number = 'EXP-'.now()->format('Ymd-His');
        $this->dispatch('close-modal');
    }

    public function selectExpense(int $expenseId): void
    {
        $expense = $this->visibleExpensesQuery()->findOrFail($expenseId);
        $this->selectedExpenseId = $expense->id;
    }

    public function markChecked(int $expenseId): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'logistics.expenses', 'edit'), 403);

        $expense = $this->visibleExpensesQuery()->findOrFail($expenseId);
        abort_unless($expense->approval_status === 'pending', 403);

        $expense->update(['approval_status' => 'checked']);
    }

    public function approveExpense(): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'logistics.expenses', 'delete'), 403);

        $expense = $this->visibleExpensesQuery()->findOrFail($this->selectedExpenseId);
        abort_unless(in_array($expense->approval_status, ['checked', 'pending'], true), 403);

        $expense->update([
            'approval_status' => 'approved',
            'approved_by_user_id' => auth()->id(),
        ]);

        $this->syncTransportCost($expense->trip);
        $this->dispatch('close-modal');
    }

    public function rejectExpense(): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'logistics.expenses', 'delete'), 403);

        $expense = $this->visibleExpensesQuery()->findOrFail($this->selectedExpenseId);

        $expense->update([
            'approval_status' => 'rejected',
            'approved_by_user_id' => auth()->id(),
        ]);

        $this->syncTransportCost($expense->trip);
        $this->dispatch('close-modal');
    }

    public function render()
    {
        return view('livewire.logistics.expenses', [
            'expenses' => $this->visibleExpensesQuery()->latest('incurred_at')->get(),
            'tripOptions' => $this->tripOptions()->all(),
            'canSubmitExpense' => Rbac::allowsRoute(auth()->user()?->role, 'logistics.expenses', 'create'),
            'canCheckExpense' => Rbac::allowsRoute(auth()->user()?->role, 'logistics.expenses', 'edit'),
            'canApproveExpense' => Rbac::allowsRoute(auth()->user()?->role, 'logistics.expenses', 'delete'),
            'selectedExpense' => $this->selectedExpenseId
                ? $this->visibleExpensesQuery()->find($this->selectedExpenseId)
                : null,
        ]);
    }

    private function visibleExpensesQuery()
    {
        $query = TripExpense::query()->with(['trip.driver.user', 'approver']);

        if (auth()->user()?->role === 'Driver') {
            $driverId = auth()->user()?->driver?->id;

            return $driverId
                ? $query->whereHas('trip', fn ($trip) => $trip->where('driver_id', $driverId))
                : $query->whereRaw('1 = 0');
        }

        return $query;
    }

    private function tripOptions(): Collection
    {
        $query = Trip::query()->whereIn('status', ['in_transit', 'completed'])->latest();

        if (auth()->user()?->role === 'Driver') {
            $driverId = auth()->user()?->driver?->id;

            if (! $driverId) {
                return collect();
            }

            $query->where('driver_id', $driverId);
        }

        return $query->pluck('trip_number');
    }

    private function canSubmitForTrip(Trip $trip): bool
    {
        if (auth()->user()?->role !== 'Driver') {
            return Rbac::allowsRoute(auth()->user()?->role, 'logistics.expenses', 'create');
        }

        return $trip->driver_id === auth()->user()?->driver?->id;
    }

    private function syncTransportCost(Trip $trip): void
    {
        $trip->loadMissing(['fuelTransactions', 'expenses', 'transportCost', 'dispatch.reservation.route', 'route']);

        $fuelCost = (float) $trip->fuelTransactions->sum('total_cost');
        $expenseCost = (float) $trip->expenses
            ->where('approval_status', 'approved')
            ->sum('amount');
        $maintenance = (float) ($trip->transportCost?->maintenance_allocation ?? 0);
        $total = $fuelCost + $expenseCost + $maintenance;
        $distance = max((float) $trip->distance_km, 1);

        TransportCost::updateOrCreate(
            ['trip_id' => $trip->id],
            [
                'fuel_cost' => $fuelCost,
                'expense_cost' => $expenseCost,
                'maintenance_allocation' => $maintenance,
                'total_cost' => $total,
                'cost_per_km' => $total / $distance,
                'center_code' => $trip->route?->center_code
                    ?? $trip->dispatch?->reservation?->route?->center_code
                    ?? $trip->transportCost?->center_code,
                'status' => 'posted',
            ]
        );
    }
}
