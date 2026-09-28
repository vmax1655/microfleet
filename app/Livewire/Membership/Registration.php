<?php

namespace App\Livewire\Membership;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Member Registration')]
class Registration extends Component
{
    public function render()
    {
        return view('livewire.membership.registration', [
            'centers' => MockData::centers(),
            'branches' => MockData::branches(),
            'officers' => MockData::officers(),
        ]);
    }
}
