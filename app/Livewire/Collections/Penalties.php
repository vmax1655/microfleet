<?php

namespace App\Livewire\Collections;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Penalties & Restructuring')]
class Penalties extends Component
{
    public function render()
    {
        $penalties = MockData::penalties();
        $requests = MockData::restructuringRequests();

        return view('livewire.collections.penalties', [
            'penalties' => $penalties,
            'requests' => $requests,
            'accrued' => collect($penalties)->where('status', 'Pending')->sum('accrued'),
            'waived' => collect($penalties)->where('status', 'Waived')->sum('accrued'),
        ]);
    }
}
