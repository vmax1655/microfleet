<?php

namespace App\Livewire\Admin;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Analytics')]
class Analytics extends Component
{
    public function render()
    {
        return view('livewire.admin.analytics', [
            'branches' => MockData::branches(),
            'products' => MockData::loanProducts(),
        ]);
    }
}
