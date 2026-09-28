<?php

namespace App\Livewire\Collections;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Daily Collection Sheet')]
class Sheet extends Component
{
    public string $date = '2026-09-09';

    public function render()
    {
        $groups = MockData::collectionSheet($this->date);

        // Shape the payload the Alpine component keeps live totals from.
        $payload = collect($groups)->map(fn ($g) => [
            'id' => $g['center']['id'],
            'name' => $g['center']['name'],
            'meeting' => $g['center']['meeting_day'].' · '.$g['center']['meeting_time'],
            'officer' => $g['center']['officer'],
            'rows' => $g['rows'],
        ])->values()->all();

        return view('livewire.collections.sheet', [
            'groups' => $groups,
            'payload' => $payload,
            'centers' => MockData::centers(),
        ]);
    }
}
