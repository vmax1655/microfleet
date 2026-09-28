<?php

namespace App\Livewire\Admin;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Portfolio at Risk')]
class ReportPar extends Component
{
    public function render()
    {
        $buckets = MockData::parBuckets();
        $portfolio = array_sum(array_column($buckets, 'amount'));
        $atRisk = $portfolio - $buckets[0]['amount'];

        return view('livewire.admin.report-par', [
            'buckets' => $buckets,
            'branches' => MockData::branches(),
            'accounts' => MockData::delinquentAccounts(),
            'portfolio' => $portfolio,
            'atRisk' => $atRisk,
        ]);
    }
}
