<?php

namespace App\Livewire;

use App\Support\FleetReports;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.dashboard', FleetReports::dashboard());
    }
}
