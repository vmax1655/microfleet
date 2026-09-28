<?php

namespace App\Livewire\Savings;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Share Capital')]
class Shares extends Component
{
    public function render()
    {
        $holdings = MockData::shareCapital();

        return view('livewire.savings.shares', [
            'holdings' => $holdings,
            'centers' => MockData::centers(),
            'totals' => [
                'shares' => array_sum(array_column($holdings, 'shares')),
                'capital' => array_sum(array_column($holdings, 'total_capital')),
            ],
        ]);
    }
}
