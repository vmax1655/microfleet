<?php

namespace App\Livewire\Membership;

use App\Support\MockData;
use Livewire\Component;

class GroupDetail extends Component
{
    public string $centerId;

    public function mount(string $center): void
    {
        $this->centerId = $center;
    }

    public function render()
    {
        $center = collect(MockData::centers())->firstWhere('id', $this->centerId)
            ?? MockData::centers()[0];

        $roster = collect(MockData::members())
            ->where('center_id', $center['id'])
            ->values()
            ->all();

        if (empty($roster)) {
            $roster = array_slice(MockData::members(), 0, 6);
        }

        return view('livewire.membership.group-detail', [
            'center' => $center,
            'roster' => $roster,
        ])->title($center['name']);
    }
}
