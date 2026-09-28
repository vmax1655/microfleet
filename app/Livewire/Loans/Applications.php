<?php

namespace App\Livewire\Loans;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Loan Applications')]
class Applications extends Component
{
    public function render()
    {
        $applications = MockData::loanApplications();

        $stages = ['Submitted', 'Under Review', 'Credit Assessment', 'Approved', 'Rejected'];

        return view('livewire.loans.applications', [
            'applications' => $applications,
            'stages' => $stages,
            'board' => collect($stages)
                ->mapWithKeys(fn ($stage) => [
                    $stage => collect($applications)->where('stage', $stage)->values()->all(),
                ])
                ->all(),
            'products' => MockData::loanProducts(),
            'officers' => MockData::officers(),
        ]);
    }
}
