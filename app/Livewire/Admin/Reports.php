<?php

namespace App\Livewire\Admin;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Reports Center')]
class Reports extends Component
{
    public function render()
    {
        return view('livewire.admin.reports', [
            'cards' => MockData::reportCards(),
        ]);
    }
}
