<?php

namespace App\Livewire\Membership;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Member Directory')]
class Directory extends Component
{
    public function render()
    {
        $members = MockData::members();

        return view('livewire.membership.directory', [
            'members' => $members,
            'centers' => MockData::centers(),
            'branches' => MockData::branches(),
            'officers' => MockData::officers(),
            'stats' => [
                'total' => 1248,
                'active' => 1102,
                'newThisMonth' => 44,
                'dormant' => 112,
            ],
        ]);
    }
}
