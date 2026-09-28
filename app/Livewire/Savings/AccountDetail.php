<?php

namespace App\Livewire\Savings;

use App\Support\MockData;
use Livewire\Component;

class AccountDetail extends Component
{
    public string $accountNo;

    public function mount(string $account): void
    {
        $this->accountNo = $account;
    }

    public function render()
    {
        $account = collect(MockData::savingsAccounts())->firstWhere('account_no', $this->accountNo)
            ?? MockData::savingsAccounts()[0];

        $ledger = MockData::savingsLedger($account['account_no']);

        return view('livewire.savings.account-detail', [
            'account' => $account,
            'ledger' => $ledger,
            'totals' => [
                'deposits' => collect($ledger)->sum('credit'),
                'withdrawals' => collect($ledger)->sum('debit'),
                'interest' => collect($ledger)->where('type', 'Interest')->sum('credit'),
            ],
        ])->title($account['account_no']);
    }
}
