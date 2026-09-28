<?php

namespace App\Livewire\Membership;

use App\Support\MockData;
use Livewire\Component;

class ReportView extends Component
{
    public string $reportKey;

    public function mount(string $report): void
    {
        $this->reportKey = $report;
    }

    public function render()
    {
        $card = collect(MockData::membershipReportCards())->firstWhere('key', $this->reportKey)
            ?? MockData::membershipReportCards()[1];

        return view('livewire.membership.report-view', [
            'card' => $card,
            'centers' => MockData::centers(),
            'growth' => MockData::memberGrowth(),
        ])->title($card['title']);
    }
}
