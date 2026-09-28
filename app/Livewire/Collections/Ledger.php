<?php

namespace App\Livewire\Collections;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Repayment Ledger')]
class Ledger extends Component
{
    public function render()
    {
        $rows = MockData::repayments();

        return view('livewire.collections.ledger', [
            'rows' => $rows,
            'centers' => MockData::centers(),
            'totals' => [
                'amount' => array_sum(array_column($rows, 'amount')),
                'posted' => collect($rows)->where('status', 'Posted')->sum('amount'),
                'gcash' => collect($rows)->where('method', 'GCash')->sum('amount'),
                'receipts' => count($rows),
            ],
        ]);
    }
}
