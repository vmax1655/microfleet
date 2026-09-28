<?php

namespace App\Livewire\Savings;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Teller Blotter')]
class Blotter extends Component
{
    public function render()
    {
        return view('livewire.savings.blotter', [
            'blotter' => MockData::blotter(),
            'denominations' => MockData::denominations(),
        ]);
    }
}
