@php
    use App\Support\Format;

    $review = $documents[2];
@endphp

<div>
    <x-breadcrumb />

    <x-page-header
        title="KYC & Documents"
        subtitle="Verify identity and eligibility documents before a member can borrow.">
        <x-slot:actions>
            <x-btn icon="download">Export queue</x-btn>
            <x-btn variant="primary" icon="upload">Upload Document</x-btn>
        </x-slot:actions>
    </x-page-header>

    <section aria-label="Document queue summary" class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card label="Awaiting Review" :value="$counts['queue']" :delta="6.0" good-direction="down" icon="clipboard-list" />
        <x-stat-card label="Verified This Month" :value="$counts['verified'] * 9" :delta="11.2" icon="shield-check" />
        <x-stat-card label="Rejected / Returned" :value="$counts['rejected']" :delta="2.4" good-direction="down" icon="file-x" />
    </section>

    <x-filter-bar search-label="Search documents" search-placeholder="Member name, member ID, or document ID…" date-range>
        <x-select label="Document type"
                  :options="['Valid ID (PhilSys)', 'Barangay Clearance', 'Proof of Billing', 'Business Permit', 'Birth Certificate', 'Co-maker Valid ID']"
                  placeholder="All types" width="w-48" />
        <x-select label="Status" :options="['Submitted', 'Under Review', 'Verified', 'Rejected']" placeholder="All statuses" width="w-40" />
    </x-filter-bar>

    <x-card flush>
        @if(count($documents) === 0)
            <x-empty-state
                icon="shield-check"
                heading="The review queue is empty"
                help="Every submitted document has been actioned. New uploads from loan officers appear here automatically."
                action-label="Upload Document" />
        @else
            <x-data-table sort-key="uploaded" sort-dir="desc" caption="KYC document review queue">
                <x-slot:head>
                    <x-th sort="member">Member</x-th>
                    <x-th sort="type">Document Type</x-th>
                    <x-th sort="uploaded">Uploaded</x-th>
                    <x-th sort="reviewer">Reviewer</x-th>
                    <x-th sort="status">Status</x-th>
                    <x-th align="right" sr-only>Actions</x-th>
                </x-slot:head>

                @foreach($documents as $i => $doc)
                    <tr data-row
                        data-member="{{ $doc['member'] }}"
                        data-type="{{ $doc['type'] }}"
                        data-uploaded="{{ $doc['uploaded'] }}"
                        data-reviewer="{{ $doc['reviewer'] }}"
                        data-status="{{ $doc['status'] }}"
                        class="transition-colors hover:bg-primary-50 {{ $i % 2 ? 'bg-neutral-50' : '' }}">

                        <td data-label="Member" class="px-3 py-2.5">
                            <div class="flex items-center gap-2.5">
                                <x-avatar :name="$doc['member']" size="sm" />
                                <div class="min-w-0">
                                    <a href="{{ route('membership.profile', $doc['member_id']) }}"
                                       class="block truncate font-medium text-neutral-800 hover:text-primary-700 hover:underline">{{ $doc['member'] }}</a>
                                    <span class="block truncate text-xs text-neutral-500">{{ $doc['member_id'] }} · {{ $doc['center'] }}</span>
                                </div>
                            </div>
                        </td>

                        <td data-label="Document Type" class="px-3 py-2.5">
                            <span class="block text-neutral-700">{{ $doc['type'] }}</span>
                            <span class="block truncate text-xs tabular-nums text-neutral-500">{{ $doc['file'] }} · {{ $doc['size'] }}</span>
                        </td>

                        <td data-label="Uploaded" class="px-3 py-2.5 tabular-nums text-neutral-600">{{ Format::date($doc['uploaded']) }}</td>
                        <td data-label="Reviewer" class="px-3 py-2.5 text-neutral-600">{{ $doc['reviewer'] }}</td>
                        <td data-label="Status" class="px-3 py-2.5"><x-status-badge :status="$doc['status']" /></td>

                        <td data-label="" class="px-3 py-2.5 text-right">
                            <x-btn size="sm" icon="eye" @click="$dispatch('open-drawer', 'kyc-review')">Review</x-btn>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>

            <x-pagination :from="1" :to="count($documents)" :total="164" :current="1" :per-page="18" />
        @endif
    </x-card>

    {{-- ---------------------------------------------------------------- review drawer --}}
    <x-drawer name="kyc-review"
              title="Review document"
              :subtitle="$review['id'].' · '.$review['type']"
              width="max-w-2xl">

        <div class="space-y-5">
            {{-- Member summary --}}
            <div class="flex items-center gap-3 rounded-[12px] border border-neutral-200 bg-neutral-50 p-4">
                <x-avatar :name="$review['member']" size="lg" />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-neutral-800">{{ $review['member'] }}</p>
                    <p class="truncate text-xs text-neutral-500">{{ $review['member_id'] }} · {{ $review['center'] }}</p>
                </div>
                <x-btn size="sm" icon="external-link" :href="route('membership.profile', $review['member_id'])">Profile</x-btn>
            </div>

            {{-- Document preview placeholder --}}
            <div>
                <div class="mb-2 flex items-center justify-between gap-2">
                    <h3 class="text-[13px] font-semibold uppercase tracking-wide text-neutral-500">Document preview</h3>
                    <div class="flex gap-1.5">
                        <x-btn size="xs" icon="download">Download</x-btn>
                        <x-btn size="xs" icon="external-link">Open full size</x-btn>
                    </div>
                </div>

                <div class="grid aspect-[4/3] w-full place-items-center rounded-[12px] border-2 border-dashed border-neutral-300 bg-neutral-100">
                    <div class="text-center">
                        <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-white text-neutral-400 shadow-card">
                            <x-icon name="image" class="h-6 w-6" />
                        </span>
                        <p class="mt-3 text-[13px] font-medium text-neutral-600">{{ $review['file'] }}</p>
                        <p class="mt-0.5 text-xs tabular-nums text-neutral-500">{{ $review['size'] }} · uploaded {{ Format::date($review['uploaded']) }}</p>
                    </div>
                </div>
            </div>

            {{-- Extracted details --}}
            <div>
                <h3 class="mb-2.5 text-[13px] font-semibold uppercase tracking-wide text-neutral-500">Details to confirm</h3>
                <dl class="grid grid-cols-1 gap-x-8 gap-y-3.5 rounded-[12px] border border-neutral-200 p-4 sm:grid-cols-2">
                    <x-kpi label="Document type" :value="$review['type']" :mono="false" />
                    <x-kpi label="ID number" value="1234-5678-9012" />
                    <x-kpi label="Name on document" :value="$review['member']" :mono="false" />
                    <x-kpi label="Date of birth" value="04 Nov 1979" />
                    <x-kpi label="Expiry date" value="12 Mar 2029" />
                    <x-kpi label="Issuing authority" value="Philippine Statistics Authority" :mono="false" />
                </dl>
            </div>

            {{-- Checklist --}}
            <div>
                <h3 class="mb-2.5 text-[13px] font-semibold uppercase tracking-wide text-neutral-500">Verification checklist</h3>
                <ul class="space-y-2.5">
                    @foreach([
                        'Name on the document matches the registration record',
                        'Photo is legible and the ID number is readable',
                        'Document is not expired',
                        'No visible signs of alteration',
                    ] as $index => $check)
                        <li class="flex items-start gap-2.5">
                            <input id="check-{{ $index }}" type="checkbox" checked
                                   class="mt-0.5 h-4 w-4 rounded-[4px] border-neutral-300 text-primary-600">
                            <label for="check-{{ $index }}" class="text-[13px] text-neutral-700">{{ $check }}</label>
                        </li>
                    @endforeach
                </ul>
            </div>

            <x-form-field label="Reviewer notes" type="textarea" name="kyc_notes" rows="3"
                          placeholder="Add context for the audit trail — required when rejecting."
                          help="Notes are written to the audit log with your name and timestamp." />
        </div>

        <x-slot:footer>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <x-btn variant="danger-outline" icon="x" @click="$dispatch('open-modal', 'reject-document')">Reject</x-btn>
                <div class="flex items-center gap-2">
                    <x-btn @click="$dispatch('close-drawer')">Cancel</x-btn>
                    <x-btn variant="primary" icon="shield-check">Verify Document</x-btn>
                </div>
            </div>
        </x-slot:footer>
    </x-drawer>

    <x-modal name="reject-document" title="Reject this document?" tone="danger" icon="alert-triangle"
             subtitle="The loan officer will be asked to re-upload a replacement.">
        <x-form-field label="Reason for rejection" type="select" name="reject_reason" required
                      :options="['Photo is blurred or unreadable', 'Document has expired', 'Name does not match the record', 'Wrong document type uploaded', 'Suspected alteration', 'Other']" />
        <x-form-field class="mt-4" label="Additional detail" type="textarea" name="reject_detail" rows="3" required
                      placeholder="Tell the officer exactly what needs to be corrected." />

        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="danger" icon="x">Reject Document</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
