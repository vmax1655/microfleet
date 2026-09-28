<?php

namespace App\Livewire\Loans;

use App\Support\MockData;
use Livewire\Component;

class AccountDetail extends Component
{
    public string $loanId;

    public function mount(string $loan): void
    {
        $this->loanId = $loan;
    }

    public function render()
    {
        $loan = MockData::loanAccount($this->loanId);
        $schedule = MockData::amortization($loan);

        return view('livewire.loans.account-detail', [
            'loan' => $loan,
            'schedule' => $schedule,
            'totals' => [
                'principal' => array_sum(array_column($schedule, 'principal')),
                'interest' => array_sum(array_column($schedule, 'interest')),
                'due' => array_sum(array_column($schedule, 'total_due')),
                'paid' => array_sum(array_column($schedule, 'amount_paid')),
            ],
        ])->title($loan['id']);
    }
}
