<?php

namespace App\Livewire\Membership;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Groups & Centers')]
class Groups extends Component
{
    public function render()
    {
        return view('livewire.membership.groups', [
            'centers' => MockData::centers(),
            'officers' => MockData::officers(),
        ]);
    }
}
