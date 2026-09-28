<?php

namespace App\Livewire\Loans;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Disbursements')]
class Disbursements extends Component
{
    public function render()
    {
        $queue = MockData::disbursementQueue();

        return view('livewire.loans.disbursements', [
            'queue' => $queue,
            'products' => MockData::loanProducts(),
            'totals' => [
                'amount' => array_sum(array_column($queue, 'amount')),
                'net' => array_sum(array_column($queue, 'net_proceeds')),
                'forRelease' => collect($queue)->where('status', 'For Release')->count(),
            ],
        ]);
    }
}
