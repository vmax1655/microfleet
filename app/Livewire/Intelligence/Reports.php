<?php

namespace App\Livewire\Intelligence;

use App\Support\FleetReports;
use App\Support\Rbac;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Logistics Reports')]
class Reports extends Component
{
    public function render()
    {
        return view('livewire.intelligence.reports', [
            'reports' => FleetReports::reportCards(),
            'recentReports' => FleetReports::recentReports(),
            'canScheduleReports' => Rbac::allowsRoute(auth()->user()?->role, 'intelligence.reports', 'edit'),
        ]);
    }
}

