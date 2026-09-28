<?php

namespace App\Livewire\Loans;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Credit Assessment')]
class Assessment extends Component
{
    public function render()
    {
        $queue = collect(MockData::loanApplications())
            ->whereIn('stage', ['Under Review', 'Credit Assessment'])
            ->values()
            ->all();

        $selected = $queue[0] ?? MockData::loanApplications()[0];

        // Flat-method amortisation: principal spread evenly, interest on the original amount.
        $periodsPerMonth = match ($selected['frequency']) {
            'Weekly' => 4,
            'Semi-monthly' => 2,
            default => 1,
        };
        $periods = max(1, $selected['term'] * $periodsPerMonth);
        $totalInterest = $selected['amount'] * ($selected['rate'] / 100) * $selected['term'];
        $amortisation = ($selected['amount'] + $totalInterest) / $periods;
        $monthlyAmortisation = ($selected['amount'] + $totalInterest) / max(1, $selected['term']);

        $member = MockData::member($selected['member_id']);
        $netIncome = $member['monthly_income'];

        return view('livewire.loans.assessment', [
            'queue' => $queue,
            'app' => $selected,
            'member' => $member,
            'amortisation' => round($amortisation, 2),
            'monthlyAmortisation' => round($monthlyAmortisation, 2),
            'totalInterest' => round($totalInterest, 2),
            'periods' => $periods,
            'capacityRatio' => round($monthlyAmortisation / max(1, $netIncome) * 100, 1),
            'netIncome' => $netIncome,
        ]);
    }
}
