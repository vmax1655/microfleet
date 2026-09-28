<?php

namespace App\Livewire\Savings;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Savings Accounts')]
class Accounts extends Component
{
    public function render()
    {
        $accounts = MockData::savingsAccounts();

        return view('livewire.savings.accounts', [
            'accounts' => $accounts,
            'totals' => [
                'balance' => array_sum(array_column($accounts, 'balance')),
                'active' => collect($accounts)->where('status', 'Active')->count(),
                'dormant' => collect($accounts)->where('status', 'Dormant')->count(),
            ],
        ]);
    }
}
