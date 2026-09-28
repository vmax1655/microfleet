<?php

namespace App\Livewire\Admin;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Branches & Settings')]
class Branches extends Component
{
    public function render()
    {
        return view('livewire.admin.branches', [
            'branches' => MockData::branches(),
        ]);
    }
}
