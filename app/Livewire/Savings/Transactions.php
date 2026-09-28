<?php

namespace App\Livewire\Savings;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Deposits & Withdrawals')]
class Transactions extends Component
{
    public function render()
    {
        $today = MockData::todaysTransactions();

        return view('livewire.savings.transactions', [
            'today' => $today,
            'accounts' => MockData::savingsAccounts(),
            'session' => [
                'deposits' => collect($today)->where('type', 'Deposit')->sum('amount'),
                'withdrawals' => collect($today)->where('type', 'Withdrawal')->sum('amount'),
                'count' => count($today),
            ],
        ]);
    }
}
