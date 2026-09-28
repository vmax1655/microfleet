<?php

namespace App\Livewire\Membership;

use App\Support\MockData;
use Livewire\Component;

class MemberProfile extends Component
{
    public string $memberId;

    public function mount(string $member): void
    {
        $this->memberId = $member;
    }

    public function render()
    {
        $member = MockData::member($this->memberId);

        $loans = collect(MockData::loanAccounts())
            ->where('member_id', $member['id'])
            ->values()
            ->all();

        // Guarantee the profile always has something to show in the demo data.
        if (empty($loans)) {
            $loans = array_slice(MockData::loanAccounts(), 0, 2);
        }

        return view('livewire.membership.member-profile', [
            'member' => $member,
            'loans' => $loans,
            'savings' => collect(MockData::savingsAccounts())->take(2)->values()->all(),
            'ledger' => array_slice(MockData::savingsLedger('SA-030001'), 0, 8),
            'repayments' => array_slice(MockData::repayments(), 0, 10),
            'documents' => array_slice(MockData::kycDocuments(), 0, 6),
        ])->title($member['name']);
    }
}
