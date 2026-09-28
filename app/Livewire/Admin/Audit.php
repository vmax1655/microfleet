<?php

namespace App\Livewire\Admin;

use App\Support\MockData;
use App\Support\Nav;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Audit Log')]
class Audit extends Component
{
    public function render()
    {
        $entries = MockData::auditLog();

        return view('livewire.admin.audit', [
            'entries' => $entries,
            'modules' => collect(Nav::modules())->pluck('label')->all(),
            'actors' => collect($entries)->pluck('actor')->unique()->values()->all(),
        ]);
    }
}
