@php
    $steps = ['Personal Info', 'Address & Contact', 'Employment & Income', 'Group Assignment', 'Documents', 'Review & Submit'];
@endphp

<div x-data="wizard({{ count($steps) }}, 3)">
    <x-breadcrumb />

    <x-page-header
        title="Member Registration"
        subtitle="Enrol a new member. Progress is saved as a draft at every step.">
        <x-slot:actions>
            <x-btn icon="folder-open">Save as draft</x-btn>
            <x-btn icon="x" :href="route('membership.directory')">Cancel</x-btn>
        </x-slot:actions>
    </x-page-header>

    {{-- Stepper --}}
    <x-card class="mb-5">
        <x-stepper :steps="$steps" />
    </x-card>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="lg:col-span-2">

            {{-- ---------------------------------------------------- Step 1 --}}
            <x-card x-show="step === 1" x-cloak title="Personal Information"
                    subtitle="As written on the member's government-issued ID">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-form-field label="First name" name="first_name" value="Melanie" required />
                    <x-form-field label="Middle name" name="middle_name" value="Sionil" />
                    <x-form-field label="Last name" name="last_name" value="Cabrera" required />
                    <x-form-field label="Suffix" name="suffix" placeholder="Jr., Sr., III" />
                    <x-form-field label="Date of birth" name="birthdate" type="date" value="1988-04-12" required tabular />
                    <x-form-field label="Sex" name="sex" type="select" :options="['Female', 'Male']" value="Female" required />
                    <x-form-field label="Civil status" name="civil_status" type="select"
                                  :options="['Single', 'Married', 'Widowed', 'Separated']" value="Married" required />
                    <x-form-field label="Place of birth" name="birthplace" value="Malolos, Bulacan" />
                    <x-form-field label="Nationality" name="nationality" value="Filipino" required />
                    <x-form-field label="Number of dependents" name="dependents" type="number" value="3" tabular />
                </div>
            </x-card>

            {{-- ---------------------------------------------------- Step 2 --}}
            <x-card x-show="step === 2" x-cloak title="Address & Contact"
                    subtitle="Where the member lives and how the officer can reach them">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-form-field class="sm:col-span-2" label="House / unit no. and street" name="street"
                                  value="Blk 7 Lot 12, Purok 3" required />
                    <x-form-field label="Barangay" name="barangay" value="Brgy. Malanday" required />
                    <x-form-field label="City / Municipality" name="city" value="Malolos" required />
                    <x-form-field label="Province" name="province" value="Bulacan" required />
                    <x-form-field label="ZIP code" name="zip" value="3000" tabular />
                    <x-form-field label="Years at this address" name="years_at_address" type="number" value="9" tabular />
                    <x-form-field label="Residence type" name="residence" type="select"
                                  :options="['Owned', 'Rented', 'Living with relatives', 'Company provided']" value="Owned" />

                    <x-form-field label="Mobile number" name="mobile" value="0905 774 1129" required tabular
                                  help="Used for SMS reminders and GCash disbursement." />
                    <x-form-field label="Email address" name="email" type="email" value="melanie.cabrera@gmail" required
                                  error="Enter a complete email address, for example name@gmail.com." />

                    <x-form-field class="sm:col-span-2" label="Emergency contact" name="emergency"
                                  value="Rodel Cabrera — 0917 448 2210 (Spouse)" />
                </div>
            </x-card>

            {{-- ---------------------------------------------------- Step 3 --}}
            <x-card x-show="step === 3" x-cloak title="Employment & Income"
                    subtitle="Used to compute the member's capacity to pay">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-form-field label="Primary livelihood" name="livelihood" type="select" required
                                  :options="['Sari-sari store', 'Market vendor – gulay', 'Carinderia', 'Palay farming', 'Tricycle operator', 'Dressmaking', 'Fish vending', 'Hog raising']"
                                  value="Sari-sari store" />
                    <x-form-field label="Years in operation" name="years_operating" type="number" value="6" tabular required />
                    <x-form-field label="Business address" name="business_address" value="Same as home address" />
                    <x-form-field label="Business permit no." name="permit_no" placeholder="Optional for micro-enterprises" tabular />

                    <x-form-field label="Average monthly sales" name="monthly_sales" prefix="₱" value="42,000" tabular required />
                    <x-form-field label="Average monthly expenses" name="monthly_expenses" prefix="₱" value="27,500" tabular required />
                    <x-form-field label="Net monthly income" name="net_income" prefix="₱" value="14,500" tabular disabled
                                  help="Computed automatically from sales less expenses." />
                    <x-form-field label="Other income sources" name="other_income" prefix="₱" value="3,000" tabular />

                    <x-form-field class="sm:col-span-2" label="Existing obligations with other lenders" name="obligations"
                                  type="textarea" rows="2"
                                  placeholder="Lender, outstanding balance, and monthly amortisation" />
                </div>
            </x-card>

            {{-- ---------------------------------------------------- Step 4 --}}
            <x-card x-show="step === 4" x-cloak title="Group Assignment"
                    subtitle="Every member belongs to a center and a 5-member solidarity group">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-form-field label="Branch" name="branch" type="select" required
                                  :options="collect($branches)->pluck('name')->all()" value="Malolos Main Branch" />
                    <x-form-field label="Center" name="center" type="select" required
                                  :options="collect($centers)->pluck('name')->all()" value="Center 02 – Brgy. Malanday" />
                    <x-form-field label="Solidarity group" name="group" type="select" required
                                  :options="['Group A – Masagana', 'Group B – Maunlad', 'Group C – Matatag', 'Group D – Malaya']"
                                  value="Group B – Maunlad" />
                    <x-form-field label="Assigned loan officer" name="officer" type="select" required
                                  :options="collect($officers)->pluck('name')->all()" value="Arnel P. Bacani" />
                    <x-form-field label="Meeting day" name="meeting_day" type="select"
                                  :options="['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']" value="Monday" disabled
                                  help="Inherited from the selected center." />
                    <x-form-field label="Initial share capital" name="shares" prefix="₱" value="500.00" tabular required
                                  help="Minimum ₱500.00 (5 shares at ₱100.00 par value)." />
                </div>

                <div class="mt-5 rounded-[12px] border border-primary-200 bg-primary-50 p-4">
                    <div class="flex gap-2.5">
                        <x-icon name="info" class="mt-0.5 shrink-0 text-primary-700" />
                        <div>
                            <p class="text-[13px] font-medium text-primary-800">Group liability applies</p>
                            <p class="mt-0.5 text-[13px] text-primary-800/80">
                                Members of a solidarity group co-guarantee each other's loans. The group must confirm
                                acceptance before the first disbursement is released.
                            </p>
                        </div>
                    </div>
                </div>
            </x-card>

            {{-- ---------------------------------------------------- Step 5 --}}
            <x-card x-show="step === 5" x-cloak title="Documents"
                    subtitle="Upload clear photos or scans. Maximum 5 MB each.">
                <ul class="space-y-3">
                    @foreach([
                        ['Valid ID (PhilSys / UMID / Driver\'s License)', true, 'philsys_cabrera.jpg', '412 KB'],
                        ['Barangay Clearance', true, 'brgy_clearance_malanday.pdf', '288 KB'],
                        ['Proof of Billing', false, null, null],
                        ['Sketch of Residence', true, 'sketch_blk7.png', '164 KB'],
                        ['Co-maker Valid ID', false, null, null],
                    ] as [$label, $uploaded, $file, $size])
                        <li class="flex flex-wrap items-center gap-3 rounded-[12px] border border-neutral-200 p-3.5">
                            <span @class([
                                'grid h-9 w-9 shrink-0 place-items-center rounded-[8px]',
                                'bg-[#E7F6EE] text-success' => $uploaded,
                                'bg-neutral-100 text-neutral-500' => ! $uploaded,
                            ])>
                                <x-icon :name="$uploaded ? 'file-check' : 'upload'" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-neutral-800">{{ $label }}</p>
                                @if($uploaded)
                                    <p class="truncate text-xs text-neutral-500 tabular-nums">{{ $file }} · {{ $size }}</p>
                                @else
                                    <p class="text-xs text-neutral-500">No file selected</p>
                                @endif
                            </div>

                            @if($uploaded)
                                <x-status-badge status="Verified">Uploaded</x-status-badge>
                                <x-btn size="sm" variant="ghost" icon="trash-2" aria-label="Remove {{ $label }}" />
                            @else
                                <x-btn size="sm" icon="upload">Choose file</x-btn>
                            @endif
                        </li>
                    @endforeach
                </ul>

                <p class="mt-4 flex items-start gap-1.5 text-xs text-danger">
                    <x-icon name="alert-circle" class="mt-px h-3.5 w-3.5 shrink-0" />
                    <span>Proof of Billing is required before the registration can be submitted for KYC review.</span>
                </p>
            </x-card>

            {{-- ---------------------------------------------------- Step 6 --}}
            <x-card x-show="step === 6" x-cloak title="Review & Submit"
                    subtitle="Check every section before submitting for KYC verification">
                <div class="space-y-5">
                    @foreach([
                        ['Personal Info', 1, [
                            'Full name' => 'Melanie Sionil Cabrera',
                            'Date of birth' => '12 Apr 1988',
                            'Sex' => 'Female',
                            'Civil status' => 'Married',
                            'Dependents' => '3',
                        ]],
                        ['Address & Contact', 2, [
                            'Address' => 'Blk 7 Lot 12, Purok 3, Brgy. Malanday, Malolos, Bulacan 3000',
                            'Mobile' => '0905 774 1129',
                            'Email' => 'melanie.cabrera@gmail',
                            'Residence' => 'Owned · 9 years',
                        ]],
                        ['Employment & Income', 3, [
                            'Livelihood' => 'Sari-sari store · 6 years',
                            'Monthly sales' => '₱42,000.00',
                            'Monthly expenses' => '₱27,500.00',
                            'Net monthly income' => '₱14,500.00',
                        ]],
                        ['Group Assignment', 4, [
                            'Branch' => 'Malolos Main Branch',
                            'Center' => 'Center 02 – Brgy. Malanday',
                            'Group' => 'Group B – Maunlad',
                            'Loan officer' => 'Arnel P. Bacani',
                            'Share capital' => '₱500.00',
                        ]],
                        ['Documents', 5, [
                            'Uploaded' => '3 of 5 required documents',
                            'Missing' => 'Proof of Billing, Co-maker Valid ID',
                        ]],
                    ] as [$section, $stepNo, $fields])
                        <section class="rounded-[12px] border border-neutral-200">
                            <header class="flex items-center justify-between gap-3 border-b border-neutral-200 bg-neutral-50 px-4 py-2.5">
                                <h3 class="text-[13px] font-semibold text-neutral-800">{{ $section }}</h3>
                                <button type="button" @click="goTo({{ $stepNo }})"
                                        class="text-[13px] font-medium text-primary-700 hover:underline">Edit</button>
                            </header>
                            <dl class="grid grid-cols-1 gap-x-8 gap-y-3 p-4 sm:grid-cols-2">
                                @foreach($fields as $k => $v)
                                    <div class="flex justify-between gap-4 sm:block">
                                        <dt class="text-xs font-medium uppercase tracking-wide text-neutral-500">{{ $k }}</dt>
                                        <dd class="text-[13px] text-neutral-800 sm:mt-1">{{ $v }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </section>
                    @endforeach

                    <div class="rounded-[12px] border border-[#F5D28A] bg-[#FEF0D6] p-4">
                        <div class="flex gap-2.5">
                            <x-icon name="alert-triangle" class="mt-0.5 shrink-0 text-warning" />
                            <div>
                                <p class="text-[13px] font-medium text-warning">2 documents still missing</p>
                                <p class="mt-0.5 text-[13px] text-warning/90">
                                    You can submit now and upload the remaining documents during KYC review, but the
                                    member cannot be issued a loan until all requirements are complete.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start gap-2">
                        <input id="consent" type="checkbox" class="mt-0.5 h-4 w-4 rounded-[4px] border-neutral-300 text-primary-600">
                        <label for="consent" class="text-[13px] text-neutral-700">
                            The member has read and signed the Data Privacy Consent form and the Membership Agreement.
                        </label>
                    </div>
                </div>
            </x-card>

            {{-- Wizard controls --}}
            <div class="mt-5 flex items-center justify-between gap-3">
                <x-btn icon="arrow-left" @click="back()" ::disabled="step === 1">Back</x-btn>

                <p class="text-[13px] text-neutral-500 tabular-nums">
                    Step <span x-text="step">1</span> of {{ count($steps) }}
                </p>

                <x-btn icon-right="arrow-right" @click="next()" x-show="step < {{ count($steps) }}">Continue</x-btn>
                <x-btn variant="primary" icon="check" x-show="step === {{ count($steps) }}" x-cloak>Submit Registration</x-btn>
            </div>
        </div>

        {{-- Side rail --}}
        <aside class="space-y-5">
            <x-card title="Requirements checklist">
                <ul class="space-y-3">
                    @foreach([
                        ['Filipino citizen, 18–65 years old', true],
                        ['Resident of the service area for 6+ months', true],
                        ['Active livelihood for at least 6 months', true],
                        ['Two valid government IDs', false],
                        ['Attended pre-membership orientation', true],
                        ['Minimum ₱500.00 share capital', true],
                    ] as [$item, $met])
                        <li class="flex items-start gap-2.5 text-[13px]">
                            <span @class([
                                'mt-0.5 grid h-4 w-4 shrink-0 place-items-center rounded-full',
                                'bg-success text-white' => $met,
                                'border border-neutral-300 bg-white' => ! $met,
                            ])>
                                @if($met)<x-icon name="check" class="h-3 w-3" stroke="3" />@endif
                            </span>
                            <span class="{{ $met ? 'text-neutral-600' : 'text-neutral-500' }}">{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-card>

            <x-card title="Need help?">
                <p class="text-[13px] leading-relaxed text-neutral-600">
                    Registrations submitted before 3:00 PM are reviewed the same banking day. Incomplete files are
                    returned to the assigned loan officer with the missing items listed.
                </p>
                <x-btn class="mt-4 w-full" icon="clipboard-list" :href="route('membership.kyc')">Go to KYC queue</x-btn>
            </x-card>
        </aside>
    </div>
</div>
