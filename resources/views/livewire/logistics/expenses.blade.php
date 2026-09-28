@php use App\Support\Format; @endphp

<div>
    <x-breadcrumb />

    <x-page-header title="Trip Expenses" subtitle="Driver-submitted trip costs checked by dispatch and approved into transport cost analysis.">
        <x-slot:actions>
            <x-btn icon="download" @click="$dispatch('open-modal', 'export-expenses')">Export</x-btn>
            @if($canSubmitExpense)
                <x-btn variant="primary" icon="receipt" @click="$dispatch('open-modal', 'expense-entry')">Log Expense</x-btn>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-card class="mt-6" flush>
        <x-slot:title>Expense Vouchers</x-slot:title>
        <x-slot:subtitle>Only approved trip expenses are included in transport cost analysis.</x-slot:subtitle>

        <x-data-table sort-key="trip" caption="Trip expense vouchers">
            <x-slot:head>
                <x-th sort="trip">Trip</x-th>
                <x-th sort="type">Type</x-th>
                <x-th sort="receipt">Receipt</x-th>
                <x-th sort="driver">Driver</x-th>
                <x-th sort="amount" align="right">Amount</x-th>
                <x-th sort="status">Status</x-th>
                <x-th align="right" sr-only>Actions</x-th>
            </x-slot:head>

            @forelse($expenses as $i => $row)
                @php
                    $status = str($row->approval_status)->replace('_', ' ')->title();
                    $type = str($row->expense_type)->replace('_', ' ')->title();
                @endphp
                <tr data-row data-trip="{{ $row->trip?->trip_number }}" data-type="{{ $type }}" data-receipt="{{ $row->receipt_number }}" data-driver="{{ $row->trip?->driver?->user?->name }}" data-amount="{{ $row->amount }}" data-status="{{ $status }}"
                    class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">
                    <td data-label="Trip" class="px-3 py-2.5 font-medium tabular-nums text-neutral-800">{{ $row->trip?->trip_number ?? '-' }}</td>
                    <td data-label="Type" class="px-3 py-2.5 text-neutral-700">{{ $type }}</td>
                    <td data-label="Receipt" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ $row->receipt_number ?: '-' }}</td>
                    <td data-label="Driver" class="px-3 py-2.5 text-neutral-600">{{ $row->trip?->driver?->user?->name ?? '-' }}</td>
                    <td data-label="Amount" class="px-3 py-2.5 text-right font-medium tabular-nums text-neutral-800">{{ Format::peso($row->amount) }}</td>
                    <td data-label="Status" class="px-3 py-2.5"><x-status-badge :status="$status" /></td>
                    <td data-label="" class="px-3 py-2.5 text-right">
                        @if($canCheckExpense && $row->approval_status === 'pending')
                            <x-btn size="sm" icon="clipboard-check" wire:click="markChecked({{ $row->id }})">Check</x-btn>
                        @elseif($canApproveExpense)
                            <x-btn size="sm" icon="check" wire:click="selectExpense({{ $row->id }})" @click="$dispatch('open-modal', 'approve-expense')">Review</x-btn>
                        @else
                            <span class="text-sm text-neutral-400">No action</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-3 py-8 text-center text-sm text-neutral-500">No trip expenses yet.</td>
                </tr>
            @endforelse
        </x-data-table>
    </x-card>

    @if($canSubmitExpense)
        <x-modal name="expense-entry" title="Log Trip Expense" subtitle="Attach incidental cost to your assigned trip." icon="receipt">
            <form id="expense-entry-form" wire:submit="submitExpense">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <x-form-field label="Trip" type="select" name="trip" wire:model="trip" :options="$tripOptions" required :error="$errors->first('trip')" />
                    <x-form-field label="Expense type" type="select" name="expense_type" wire:model="expense_type" :options="['Toll', 'Parking', 'Meal Allowance', 'Emergency Repair', 'Loading Fee']" required :error="$errors->first('expense_type')" />
                    <x-form-field label="Receipt no." name="receipt_number" wire:model="receipt_number" :error="$errors->first('receipt_number')" />
                    <x-form-field label="Amount" type="number" name="amount" wire:model="amount" prefix="PHP" required tabular :error="$errors->first('amount')" />
                    <x-form-field label="Incurred date" type="date" name="incurred_at" wire:model="incurred_at" required :error="$errors->first('incurred_at')" />
                </div>
                <x-form-field class="mt-3" label="Description" type="textarea" name="description" wire:model="description" rows="1" placeholder="Short explanation for audit trail" :error="$errors->first('description')" />
            </form>
            <x-slot:footer>
                <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
                <x-btn variant="primary" icon="check" type="submit" form="expense-entry-form">Submit Expense</x-btn>
            </x-slot:footer>
        </x-modal>
    @endif

    <x-modal name="approve-expense" title="Approve Expense Voucher" subtitle="Approval writes this voucher into the transport cost rollup." icon="check" tone="success">
        @if($selectedExpense)
            <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-neutral-500">Trip</dt>
                    <dd class="font-medium text-neutral-800">{{ $selectedExpense->trip?->trip_number }}</dd>
                </div>
                <div>
                    <dt class="text-neutral-500">Amount</dt>
                    <dd class="font-medium text-neutral-800">{{ Format::peso($selectedExpense->amount) }}</dd>
                </div>
                <div>
                    <dt class="text-neutral-500">Receipt</dt>
                    <dd class="font-medium text-neutral-800">{{ $selectedExpense->receipt_number ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-neutral-500">Status</dt>
                    <dd class="font-medium text-neutral-800">{{ str($selectedExpense->approval_status)->replace('_', ' ')->title() }}</dd>
                </div>
            </dl>
        @endif
        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            @if($selectedExpense && in_array($selectedExpense->approval_status, ['checked', 'pending'], true))
                <x-btn variant="danger" icon="x" wire:click="rejectExpense">Reject</x-btn>
                <x-btn variant="success" icon="check" wire:click="approveExpense">Approve</x-btn>
            @endif
        </x-slot:footer>
    </x-modal>

    <x-modal name="export-expenses" title="Export Trip Expenses" icon="download">
        <x-form-field label="Format" type="select" name="format" :options="['CSV', 'PDF']" />
        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="primary" icon="download" @click="$dispatch('close-modal')">Download</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
