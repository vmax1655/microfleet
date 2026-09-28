<?php

namespace App\Livewire\Loans;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Loan Accounts')]
class Accounts extends Component
{
    public function render()
    {
        $loans = MockData::loanAccounts();

        return view('livewire.loans.accounts', [
            'loans' => $loans,
            'products' => MockData::loanProducts(),
            'officers' => MockData::officers(),
            'totals' => [
                'outstanding' => array_sum(array_column($loans, 'outstanding')),
                'principal' => array_sum(array_column($loans, 'principal')),
                'overdue' => collect($loans)->where('status', 'Overdue')->count(),
            ],
        ]);
    }
}
