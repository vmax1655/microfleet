<?php

namespace App\Livewire\Membership;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Membership Reports')]
class Reports extends Component
{
    public function render()
    {
        return view('livewire.membership.reports', [
            'cards' => MockData::membershipReportCards(),
        ]);
    }
}
