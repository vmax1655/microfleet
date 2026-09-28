<?php

namespace App\Livewire\Membership;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('KYC & Documents')]
class Kyc extends Component
{
    public function render()
    {
        $documents = MockData::kycDocuments();

        return view('livewire.membership.kyc', [
            'documents' => $documents,
            'counts' => [
                'queue' => collect($documents)->whereIn('status', ['Submitted', 'Under Review'])->count(),
                'verified' => collect($documents)->where('status', 'Verified')->count(),
                'rejected' => collect($documents)->where('status', 'Rejected')->count(),
            ],
        ]);
    }
}
