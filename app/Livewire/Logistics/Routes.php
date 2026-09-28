<?php

namespace App\Livewire\Logistics;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Center Routes')]
class Routes extends Component
{
    public function render()
    {
        return view('livewire.logistics.routes');
    }
}

