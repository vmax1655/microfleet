<?php

namespace App\Livewire\Loans;

use App\Support\MockData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Loan Products')]
class Products extends Component
{
    public function render()
    {
        return view('livewire.loans.products', [
            'products' => MockData::loanProducts(),
        ]);
    }
}
