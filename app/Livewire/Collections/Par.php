<?php

namespace App\Livewire\Collections;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Past Due & PAR')]
class Par extends Component
{
    public function render()
    {
        return view('livewire.collections.par', [
            'accounts' => MockData::delinquentAccounts(),
            'buckets' => MockData::parBuckets(),
            'officers' => MockData::officers(),
            'centers' => MockData::centers(),
        ]);
    }
}
