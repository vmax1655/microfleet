<?php

namespace App\Livewire\Collections;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Collection Performance')]
class Performance extends Component
{
    public function render()
    {
        $officers = MockData::officerPerformance();

        return view('livewire.collections.performance', [
            'officers' => $officers,
            'centers' => MockData::centers(),
            'totals' => [
                'target' => array_sum(array_column($officers, 'target')),
                'collected' => array_sum(array_column($officers, 'collected')),
                'accounts' => array_sum(array_column($officers, 'accounts')),
            ],
        ]);
    }
}
