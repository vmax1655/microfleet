<?php

namespace App\Livewire\Savings;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Interest Posting')]
class Interest extends Component
{
    public function render()
    {
        $preview = MockData::interestPreview();
        $eligible = collect($preview)->where('status', 'Pending');

        return view('livewire.savings.interest', [
            'preview' => $preview,
            'totals' => [
                'interest' => $eligible->sum('interest'),
                'balance' => $eligible->sum('balance'),
                'accounts' => $eligible->count(),
                'skipped' => collect($preview)->where('status', 'Skipped')->count(),
            ],
        ]);
    }
}
