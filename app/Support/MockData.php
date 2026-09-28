<?php

namespace App\Support;

/**
 * Presentation-layer mock data.
 *
 * Every screen in Ledger is prop-driven: Livewire components pull arrays from
 * here and pass them into Blade components. When the real Eloquent models land,
 * only these methods need to be swapped for repository/query calls — no view
 * markup has to change.
 *
 * All figures are deterministic so screenshots and demos stay stable.
 */
class MockData
{
    // ---------------------------------------------------------------- reference

    public static function branches(): array
    {
        return [
            ['id' => 'BR-01', 'name' => 'Malolos Main Branch', 'city' => 'Malolos, Bulacan', 'manager' => 'Teresita G. Gonzales', 'members' => 1248, 'portfolio' => 41850000, 'par30' => 3.8, 'opened' => '2016-03-14'],
            ['id' => 'BR-02', 'name' => 'Sta. Maria Branch', 'city' => 'Sta. Maria, Bulacan', 'manager' => 'Benigno L. Ocampo', 'members' => 864, 'portfolio' => 27340000, 'par30' => 4.6, 'opened' => '2018-07-02'],
            ['id' => 'BR-03', 'name' => 'San Jose del Monte Branch', 'city' => 'SJDM, Bulacan', 'manager' => 'Marilou F. Fernandez', 'members' => 1032, 'portfolio' => 33120000, 'par30' => 5.2, 'opened' => '2019-01-21'],
            ['id' => 'BR-04', 'name' => 'Baliuag Branch', 'city' => 'Baliuag, Bulacan', 'manager' => 'Rogelio C. Castillo', 'members' => 611, 'portfolio' => 18470000, 'par30' => 2.9, 'opened' => '2021-05-10'],
            ['id' => 'BR-05', 'name' => 'Plaridel Branch', 'city' => 'Plaridel, Bulacan', 'manager' => 'Divina S. Cabrera', 'members' => 487, 'portfolio' => 12960000, 'par30' => 3.4, 'opened' => '2023-02-06'],
        ];
    }

    public static function officers(): array
    {
        return [
            ['id' => 'LO-101', 'name' => 'Arnel P. Bacani', 'branch' => 'Malolos Main Branch', 'centers' => 6],
            ['id' => 'LO-102', 'name' => 'Grace M. Villamor', 'branch' => 'Malolos Main Branch', 'centers' => 5],
            ['id' => 'LO-103', 'name' => 'Dennis R. Alonzo', 'branch' => 'Sta. Maria Branch', 'centers' => 5],
            ['id' => 'LO-104', 'name' => 'Maricel T. Ordoñez', 'branch' => 'San Jose del Monte Branch', 'centers' => 7],
            ['id' => 'LO-105', 'name' => 'Jomar S. Pineda', 'branch' => 'Baliuag Branch', 'centers' => 4],
        ];
    }

    public static function centers(): array
    {
        return [
            ['id' => 'CTR-01', 'name' => 'Center 01 – Brgy. Bagong Silang', 'meeting_day' => 'Monday', 'meeting_time' => '8:00 AM', 'members' => 32, 'portfolio' => 1284500, 'repayment_rate' => 98.4, 'officer' => 'Arnel P. Bacani', 'branch' => 'Malolos Main Branch'],
            ['id' => 'CTR-02', 'name' => 'Center 02 – Brgy. Malanday', 'meeting_day' => 'Monday', 'meeting_time' => '10:00 AM', 'members' => 28, 'portfolio' => 1042000, 'repayment_rate' => 96.1, 'officer' => 'Arnel P. Bacani', 'branch' => 'Malolos Main Branch'],
            ['id' => 'CTR-03', 'name' => 'Center 03 – Brgy. Poblacion', 'meeting_day' => 'Tuesday', 'meeting_time' => '8:30 AM', 'members' => 41, 'portfolio' => 1876300, 'repayment_rate' => 99.2, 'officer' => 'Grace M. Villamor', 'branch' => 'Malolos Main Branch'],
            ['id' => 'CTR-04', 'name' => 'Center 04 – Brgy. San Isidro', 'meeting_day' => 'Tuesday', 'meeting_time' => '1:00 PM', 'members' => 36, 'portfolio' => 1518900, 'repayment_rate' => 94.7, 'officer' => 'Grace M. Villamor', 'branch' => 'Malolos Main Branch'],
            ['id' => 'CTR-05', 'name' => 'Center 05 – Brgy. Sta. Cruz', 'meeting_day' => 'Wednesday', 'meeting_time' => '8:00 AM', 'members' => 24, 'portfolio' => 883400, 'repayment_rate' => 91.3, 'officer' => 'Dennis R. Alonzo', 'branch' => 'Sta. Maria Branch'],
            ['id' => 'CTR-06', 'name' => 'Center 06 – Brgy. Mabini', 'meeting_day' => 'Wednesday', 'meeting_time' => '2:00 PM', 'members' => 30, 'portfolio' => 1197600, 'repayment_rate' => 97.8, 'officer' => 'Dennis R. Alonzo', 'branch' => 'Sta. Maria Branch'],
            ['id' => 'CTR-07', 'name' => 'Center 07 – Brgy. Concepcion', 'meeting_day' => 'Thursday', 'meeting_time' => '9:00 AM', 'members' => 38, 'portfolio' => 1642800, 'repayment_rate' => 95.5, 'officer' => 'Maricel T. Ordoñez', 'branch' => 'San Jose del Monte Branch'],
            ['id' => 'CTR-08', 'name' => 'Center 08 – Brgy. San Roque', 'meeting_day' => 'Friday', 'meeting_time' => '8:00 AM', 'members' => 27, 'portfolio' => 964200, 'repayment_rate' => 89.6, 'officer' => 'Jomar S. Pineda', 'branch' => 'Baliuag Branch'],
        ];
    }

    public static function loanProducts(): array
    {
        return [
            [
                'id' => 'LP-01', 'name' => 'Sikap Micro Loan', 'code' => 'SIKAP',
                'rate' => 2.5, 'method' => 'Flat', 'term_min' => 3, 'term_max' => 12,
                'amount_min' => 5000, 'amount_max' => 50000, 'frequency' => 'Weekly',
                'penalty_rate' => 2.0, 'processing_fee' => 3.0, 'grace_days' => 3,
                'active_loans' => 412, 'portfolio' => 14820000, 'status' => 'Active',
                'description' => 'Working-capital loan for sari-sari stores and market vendors.',
            ],
            [
                'id' => 'LP-02', 'name' => 'Negosyo Plus', 'code' => 'NEGPLUS',
                'rate' => 2.0, 'method' => 'Diminishing', 'term_min' => 6, 'term_max' => 24,
                'amount_min' => 20000, 'amount_max' => 150000, 'frequency' => 'Monthly',
                'penalty_rate' => 1.5, 'processing_fee' => 2.5, 'grace_days' => 5,
                'active_loans' => 187, 'portfolio' => 12640000, 'status' => 'Active',
                'description' => 'Expansion financing for established microenterprises.',
            ],
            [
                'id' => 'LP-03', 'name' => 'Pantawid Emergency Loan', 'code' => 'PANTAWID',
                'rate' => 3.0, 'method' => 'Flat', 'term_min' => 3, 'term_max' => 6,
                'amount_min' => 5000, 'amount_max' => 15000, 'frequency' => 'Weekly',
                'penalty_rate' => 2.0, 'processing_fee' => 2.0, 'grace_days' => 2,
                'active_loans' => 264, 'portfolio' => 2180000, 'status' => 'Active',
                'description' => 'Short-tenor calamity and medical emergency assistance.',
            ],
            [
                'id' => 'LP-04', 'name' => 'Agri-Asenso Loan', 'code' => 'AGRI',
                'rate' => 1.8, 'method' => 'Diminishing', 'term_min' => 6, 'term_max' => 12,
                'amount_min' => 25000, 'amount_max' => 100000, 'frequency' => 'Semi-monthly',
                'penalty_rate' => 1.5, 'processing_fee' => 2.0, 'grace_days' => 7,
                'active_loans' => 96, 'portfolio' => 6740000, 'status' => 'Active',
                'description' => 'Crop-cycle aligned financing for palay and vegetable farmers.',
            ],
            [
                'id' => 'LP-05', 'name' => 'Kabuhayan Group Loan', 'code' => 'KABUHAYAN',
                'rate' => 2.2, 'method' => 'Flat', 'term_min' => 4, 'term_max' => 12,
                'amount_min' => 5000, 'amount_max' => 30000, 'frequency' => 'Weekly',
                'penalty_rate' => 2.0, 'processing_fee' => 2.5, 'grace_days' => 3,
                'active_loans' => 331, 'portfolio' => 5470000, 'status' => 'Active',
                'description' => 'Group-guaranteed loan released to 5-member solidarity groups.',
            ],
            [
                'id' => 'LP-06', 'name' => 'Kabataan Starter Loan', 'code' => 'KABATAAN',
                'rate' => 2.4, 'method' => 'Flat', 'term_min' => 3, 'term_max' => 9,
                'amount_min' => 5000, 'amount_max' => 20000, 'frequency' => 'Weekly',
                'penalty_rate' => 2.0, 'processing_fee' => 2.0, 'grace_days' => 3,
                'active_loans' => 0, 'portfolio' => 0, 'status' => 'Draft',
                'description' => 'Pilot product for 18–25 year-old first-time borrowers.',
            ],
        ];
    }

    // ---------------------------------------------------------------- membership

    public static function members(): array
    {
        $rows = [
            ['Maria Liwayway Santos', 'F', 'CTR-04', '0917 845 2210', 'Active', 3, 'Arnel P. Bacani'],
            ['Jose Antonio Dela Cruz', 'M', 'CTR-01', '0918 223 7741', 'Active', 1, 'Arnel P. Bacani'],
            ['Rosalinda M. Bautista', 'F', 'CTR-03', '0906 771 3390', 'Active', 2, 'Grace M. Villamor'],
            ['Ernesto V. Villanueva', 'M', 'CTR-02', '0921 447 8812', 'Overdue', 1, 'Arnel P. Bacani'],
            ['Cristina P. Mercado', 'F', 'CTR-04', '0995 336 1180', 'Active', 2, 'Grace M. Villamor'],
            ['Ricardo B. Panganiban', 'M', 'CTR-05', '0917 002 5563', 'Pending', 0, 'Dennis R. Alonzo'],
            ['Lourdes A. Aquino', 'F', 'CTR-03', '0999 114 8827', 'Active', 1, 'Grace M. Villamor'],
            ['Danilo S. Ramos', 'M', 'CTR-06', '0908 553 2214', 'Active', 2, 'Dennis R. Alonzo'],
            ['Emilia C. Salazar', 'F', 'CTR-07', '0927 889 4471', 'Active', 1, 'Maricel T. Ordoñez'],
            ['Rogelio T. Castillo', 'M', 'CTR-01', '0916 320 7784', 'Dormant', 0, 'Arnel P. Bacani'],
            ['Josefina R. Delos Reyes', 'F', 'CTR-04', '0947 651 2093', 'Active', 2, 'Grace M. Villamor'],
            ['Alfredo M. Magsaysay', 'M', 'CTR-08', '0930 774 1128', 'Overdue', 1, 'Jomar S. Pineda'],
            ['Teresita G. Gonzales', 'F', 'CTR-03', '0917 664 0092', 'Active', 1, 'Grace M. Villamor'],
            ['Benigno L. Ocampo', 'M', 'CTR-05', '0918 990 7756', 'Active', 1, 'Dennis R. Alonzo'],
            ['Marilou F. Fernandez', 'F', 'CTR-07', '0905 218 3364', 'Active', 3, 'Maricel T. Ordoñez'],
            ['Corazon D. Mendoza', 'F', 'CTR-02', '0977 443 1108', 'Active', 1, 'Arnel P. Bacani'],
            ['Wilfredo A. Tolentino', 'M', 'CTR-06', '0919 872 4450', 'Closed', 0, 'Dennis R. Alonzo'],
            ['Analyn B. Pascual', 'F', 'CTR-01', '0946 117 8823', 'Active', 2, 'Arnel P. Bacani'],
            ['Eduardo N. Sarmiento', 'M', 'CTR-08', '0917 335 6621', 'Active', 1, 'Jomar S. Pineda'],
            ['Rowena L. Estrada', 'F', 'CTR-04', '0928 664 9910', 'Active', 1, 'Grace M. Villamor'],
            ['Ferdinand C. Lagman', 'M', 'CTR-03', '0915 228 7734', 'Active', 2, 'Grace M. Villamor'],
            ['Gemma S. Rivera', 'F', 'CTR-07', '0936 118 4472', 'Pending', 0, 'Maricel T. Ordoñez'],
            ['Joel P. Navarro', 'M', 'CTR-05', '0917 771 2238', 'Active', 1, 'Dennis R. Alonzo'],
            ['Imelda R. Dizon', 'F', 'CTR-02', '0908 442 6619', 'Active', 2, 'Arnel P. Bacani'],
            ['Rodel A. Marquez', 'M', 'CTR-06', '0921 337 8845', 'Overdue', 1, 'Dennis R. Alonzo'],
            ['Cherry Ann V. Bringas', 'F', 'CTR-01', '0999 552 1173', 'Active', 1, 'Arnel P. Bacani'],
            ['Michael Angelo T. Reyes', 'M', 'CTR-08', '0917 448 9902', 'Active', 1, 'Jomar S. Pineda'],
            ['Precious Grace M. Domingo', 'F', 'CTR-03', '0927 116 5540', 'Active', 2, 'Grace M. Villamor'],
            ['Rommel B. Trinidad', 'M', 'CTR-04', '0918 663 2217', 'Active', 1, 'Grace M. Villamor'],
            ['Susan G. Gatchalian', 'F', 'CTR-07', '0946 220 8871', 'Active', 1, 'Maricel T. Ordoñez'],
            ['Alberto R. Nuñez', 'M', 'CTR-05', '0917 889 3306', 'Dormant', 0, 'Dennis R. Alonzo'],
            ['Melanie S. Sionil', 'F', 'CTR-02', '0905 774 1129', 'Active', 2, 'Arnel P. Bacani'],
        ];

        $barangays = [
            'Blk 7 Lot 12, Brgy. Bagong Silang, Malolos, Bulacan',
            '221 Purok 3, Brgy. Malanday, Malolos, Bulacan',
            '48 M.H. del Pilar St., Brgy. Poblacion, Malolos, Bulacan',
            'Sitio Kaunlaran, Brgy. San Isidro, Malolos, Bulacan',
            '17 Rizal Ave., Brgy. Sta. Cruz, Sta. Maria, Bulacan',
            'Purok 5, Brgy. Mabini, Sta. Maria, Bulacan',
            'Blk 22 Lot 4, Brgy. Concepcion, San Jose del Monte, Bulacan',
            '9 Bonifacio St., Brgy. San Roque, Baliuag, Bulacan',
        ];

        $livelihoods = ['Sari-sari store', 'Market vendor – gulay', 'Carinderia', 'Palay farming', 'Tricycle operator', 'Dressmaking', 'Fish vending', 'Hog raising', 'Rice retailing', 'Buy and sell – ukay'];

        $centers = collect(self::centers())->keyBy('id');

        return collect($rows)->values()->map(function ($r, $i) use ($barangays, $livelihoods, $centers) {
            [$name, $sex, $centerId, $contact, $status, $activeLoans, $officer] = $r;
            $center = $centers[$centerId];
            $outstanding = $activeLoans === 0 ? 0 : round(8400 + ($i * 2137) % 62000, 2);
            $borrowed = $outstanding === 0 ? round(12000 + ($i * 4211) % 90000, 2) : round($outstanding * (1.9 + ($i % 7) / 10), 2);

            return [
                'id' => 'MBR-'.str_pad((string) (1001 + $i), 5, '0', STR_PAD_LEFT),
                'name' => $name,
                'sex' => $sex,
                'birthdate' => sprintf('19%02d-%02d-%02d', 62 + ($i % 28), 1 + ($i % 12), 1 + ($i % 27)),
                'contact' => $contact,
                'email' => strtolower(str_replace([' ', '.', 'ñ'], ['', '', 'n'], explode(' ', $name)[0])).($i + 1).'@gmail.com',
                'address' => $barangays[$i % count($barangays)],
                'center_id' => $centerId,
                'center' => $center['name'],
                'branch' => $center['branch'],
                'officer' => $officer,
                'status' => $status,
                'active_loans' => $activeLoans,
                'outstanding' => $outstanding,
                'total_borrowed' => $borrowed,
                'savings' => round(1500 + ($i * 1873) % 48000, 2),
                'shares' => 200 + ($i % 9) * 50,
                'on_time_rate' => round(72 + (($i * 13) % 28), 1),
                'joined' => sprintf('20%02d-%02d-%02d', 17 + ($i % 9), 1 + ($i % 12), 2 + ($i % 26)),
                'livelihood' => $livelihoods[$i % count($livelihoods)],
                'monthly_income' => 9000 + ($i % 14) * 1450,
                'household_size' => 3 + ($i % 5),
                'kyc' => $status === 'Pending' ? 'Under Review' : 'Verified',
            ];
        })->all();
    }

    public static function member(string $id): ?array
    {
        return collect(self::members())->firstWhere('id', $id) ?? self::members()[0];
    }

    public static function kycDocuments(): array
    {
        $types = ['Valid ID (PhilSys)', 'Barangay Clearance', 'Proof of Billing', 'Business Permit', 'Birth Certificate', 'Co-maker Valid ID', 'Sketch of Residence', 'Income Statement'];
        $statuses = ['Verified', 'Under Review', 'Rejected', 'Submitted', 'Verified', 'Under Review'];
        $reviewers = ['Teresita G. Gonzales', 'Benigno L. Ocampo', 'Marilou F. Fernandez', '—'];

        return collect(self::members())->take(18)->values()->map(fn ($m, $i) => [
            'id' => 'DOC-'.str_pad((string) (5001 + $i), 5, '0', STR_PAD_LEFT),
            'member' => $m['name'],
            'member_id' => $m['id'],
            'center' => $m['center'],
            'type' => $types[$i % count($types)],
            'uploaded' => sprintf('2026-0%d-%02d', 7 + ($i % 3), 3 + ($i % 25)),
            'reviewer' => $statuses[$i % count($statuses)] === 'Submitted' ? '—' : $reviewers[$i % count($reviewers)],
            'status' => $statuses[$i % count($statuses)],
            'file' => 'philsys_id_'.strtolower(explode(' ', $m['name'])[0]).'.jpg',
            'size' => (240 + $i * 37).' KB',
            'note' => $statuses[$i % count($statuses)] === 'Rejected' ? 'Photo is blurred — ID number not readable.' : '',
        ])->all();
    }

    // ---------------------------------------------------------------- loans

    public static function loanApplications(): array
    {
        $stages = ['Submitted', 'Under Review', 'Credit Assessment', 'Approved', 'Rejected'];
        $products = self::loanProducts();
        $purposes = ['Additional stock for sari-sari store', 'Expansion of carinderia', 'Palay production inputs', 'Tricycle unit downpayment', 'School expenses / enrolment', 'Hog fattening capital', 'Dressmaking equipment', 'Medical emergency'];

        return collect(self::members())->take(22)->values()->map(function ($m, $i) use ($stages, $products, $purposes) {
            $product = $products[$i % 5];
            $amount = $product['amount_min'] + (($i * 7300) % max(1, $product['amount_max'] - $product['amount_min']));
            $amount = (int) (round($amount / 500) * 500);

            return [
                'id' => 'APP-2026-'.str_pad((string) (3001 + $i), 4, '0', STR_PAD_LEFT),
                'member' => $m['name'],
                'member_id' => $m['id'],
                'center' => $m['center'],
                'branch' => $m['branch'],
                'officer' => $m['officer'],
                'product' => $product['name'],
                'product_id' => $product['id'],
                'rate' => $product['rate'],
                'method' => $product['method'],
                'frequency' => $product['frequency'],
                'amount' => $amount,
                'term' => $product['term_min'] + ($i % max(1, $product['term_max'] - $product['term_min'])),
                'purpose' => $purposes[$i % count($purposes)],
                'stage' => $stages[$i % 5],
                'status' => $stages[$i % 5],
                'days_in_stage' => 1 + (($i * 3) % 11),
                'submitted' => sprintf('2026-08-%02d', 3 + ($i % 26)),
                'credit_score' => 540 + (($i * 37) % 300),
                'capacity_ratio' => round(18 + (($i * 11) % 42), 1),
                'comaker' => self::members()[($i + 9) % 32]['name'],
                'comaker_relation' => ['Spouse', 'Sibling', 'Neighbor', 'Parent'][$i % 4],
                'collateral' => ['Chattel – tricycle unit', 'None (group guarantee)', 'Post-dated checks', 'Deposit hold-out'][$i % 4],
            ];
        })->all();
    }

    public static function loanAccounts(): array
    {
        $products = self::loanProducts();
        $statuses = ['Active', 'Active', 'Overdue', 'Active', 'Paid', 'Active', 'Restructured', 'Active'];

        return collect(self::members())->filter(fn ($m) => $m['active_loans'] > 0)->values()->map(function ($m, $i) use ($products, $statuses) {
            $product = $products[$i % 5];
            $principal = (int) (round(($product['amount_min'] + (($i * 8700) % max(1, $product['amount_max'] - $product['amount_min']))) / 500) * 500);
            $term = $product['term_min'] + ($i % max(1, $product['term_max'] - $product['term_min']));
            $disbursed = sprintf('2026-%02d-%02d', 1 + ($i % 8), 4 + ($i % 24));
            $status = $statuses[$i % count($statuses)];
            $paidRatio = $status === 'Paid' ? 1.0 : min(0.92, 0.15 + (($i * 7) % 70) / 100);
            $totalDue = round($principal * (1 + ($product['rate'] / 100) * $term), 2);
            $outstanding = $status === 'Paid' ? 0 : round($totalDue * (1 - $paidRatio), 2);
            $dpd = $status === 'Overdue' ? 7 + (($i * 13) % 110) : 0;

            return [
                'id' => 'LN-2026-'.str_pad((string) (7001 + $i), 4, '0', STR_PAD_LEFT),
                'member' => $m['name'],
                'member_id' => $m['id'],
                'center' => $m['center'],
                'branch' => $m['branch'],
                'officer' => $m['officer'],
                'product' => $product['name'],
                'rate' => $product['rate'],
                'method' => $product['method'],
                'frequency' => $product['frequency'],
                'principal' => $principal,
                'term' => $term,
                'disbursed_on' => $disbursed,
                'maturity_on' => date('Y-m-d', strtotime($disbursed.' +'.$term.' months')),
                'total_due' => $totalDue,
                'outstanding' => $outstanding,
                'paid_to_date' => round($totalDue - $outstanding, 2),
                'progress' => (int) round($paidRatio * 100),
                'next_due' => $status === 'Paid' ? null : sprintf('2026-09-%02d', 10 + ($i % 18)),
                'next_due_amount' => round($totalDue / max(1, $term * 4), 2),
                'dpd' => $dpd,
                'status' => $status,
                'collateral' => ['Chattel – tricycle unit', 'None (group guarantee)', 'Post-dated checks', 'Deposit hold-out'][$i % 4],
                'comaker' => self::members()[($i + 5) % 32]['name'],
                'comaker_contact' => self::members()[($i + 5) % 32]['contact'],
            ];
        })->all();
    }

    public static function loanAccount(string $id): array
    {
        return collect(self::loanAccounts())->firstWhere('id', $id) ?? self::loanAccounts()[0];
    }

    /**
     * Amortization schedule generator.
     *
     * Flat method: interest is a fixed % of the original principal per period.
     * Diminishing: interest accrues on the declining outstanding balance.
     */
    public static function amortization(array $loan): array
    {
        $periodsPerMonth = match ($loan['frequency']) {
            'Weekly' => 4,
            'Semi-monthly' => 2,
            'Daily' => 22,
            default => 1,
        };

        $n = max(1, $loan['term'] * $periodsPerMonth);
        $principal = (float) $loan['principal'];
        $monthlyRate = $loan['rate'] / 100;
        $periodRate = $monthlyRate / $periodsPerMonth;

        $principalPer = round($principal / $n, 2);
        $balance = $principal;
        $paidPeriods = (int) round(($loan['progress'] / 100) * $n);

        $start = strtotime($loan['disbursed_on']);
        $stepDays = match ($loan['frequency']) {
            'Weekly' => 7,
            'Semi-monthly' => 15,
            'Daily' => 1,
            default => 30,
        };

        $rows = [];
        $today = strtotime('2026-09-09');

        for ($i = 1; $i <= $n; $i++) {
            $interest = $loan['method'] === 'Flat'
                ? round($principal * $periodRate, 2)
                : round($balance * $periodRate, 2);

            $principalDue = $i === $n ? round($balance, 2) : $principalPer;
            $totalDue = round($principalDue + $interest, 2);
            $ending = round($balance - $principalDue, 2);
            $dueDate = date('Y-m-d', strtotime("+{$stepDays} days", $start + ($i - 1) * $stepDays * 86400));

            $isPaid = $i <= $paidPeriods;
            $isOverdue = ! $isPaid && strtotime($dueDate) < $today;

            $rows[] = [
                'no' => $i,
                'due_date' => $dueDate,
                'beginning' => round($balance, 2),
                'principal' => $principalDue,
                'interest' => $interest,
                'total_due' => $totalDue,
                'amount_paid' => $isPaid ? $totalDue : 0.0,
                'date_paid' => $isPaid ? date('Y-m-d', strtotime($dueDate.' -'.($i % 3).' days')) : null,
                'ending' => max(0, $ending),
                'status' => $isPaid ? 'Paid' : ($isOverdue ? 'Overdue' : (strtotime($dueDate) <= strtotime('+7 days', $today) ? 'Due Soon' : 'Upcoming')),
            ];

            $balance = $ending;
        }

        return $rows;
    }

    public static function disbursementQueue(): array
    {
        $approvers = ['Teresita G. Gonzales', 'Benigno L. Ocampo', 'Marilou F. Fernandez', 'Rogelio C. Castillo'];
        $methods = ['Cash', 'GCash', 'Bank Transfer', 'Cash', 'GCash'];
        $statuses = ['For Release', 'For Release', 'On Hold', 'For Release', 'Released', 'For Release'];

        return collect(self::loanApplications())->take(12)->values()->map(fn ($a, $i) => [
            'id' => 'DIS-2026-'.str_pad((string) (9001 + $i), 4, '0', STR_PAD_LEFT),
            'loan_id' => 'LN-2026-'.str_pad((string) (7101 + $i), 4, '0', STR_PAD_LEFT),
            'member' => $a['member'],
            'member_id' => $a['member_id'],
            'center' => $a['center'],
            'amount' => $a['amount'],
            'net_proceeds' => round($a['amount'] * 0.97, 2),
            'product' => $a['product'],
            'approved_by' => $approvers[$i % count($approvers)],
            'approved_on' => sprintf('2026-09-%02d', 1 + ($i % 8)),
            'method' => $methods[$i % count($methods)],
            'gcash' => $methods[$i % count($methods)] === 'GCash'
                ? '0917 '.(100 + $i).' '.(1000 + $i * 7)
                : '',
            'status' => $statuses[$i % count($statuses)],
        ])->all();
    }

    // ---------------------------------------------------------------- collections

    public static function collectionSheet(string $date = '2026-09-09'): array
    {
        $members = self::members();
        $centers = collect(self::centers())->take(3)->values();
        $out = [];
        $cursor = 0;

        foreach ($centers as $c) {
            $rows = [];
            $count = $c['id'] === 'CTR-01' ? 6 : ($c['id'] === 'CTR-02' ? 5 : 6);

            for ($i = 0; $i < $count; $i++) {
                $m = $members[($cursor + $i) % count($members)];
                $due = round(450 + ((($cursor + $i) * 317) % 2400), 2);
                $collected = $i % 4 === 3 ? 0.0 : ($i % 5 === 1 ? round($due / 2, 2) : $due);

                $rows[] = [
                    'member' => $m['name'],
                    'member_id' => $m['id'],
                    'loan_id' => 'LN-2026-'.str_pad((string) (7201 + $cursor + $i), 4, '0', STR_PAD_LEFT),
                    'due' => $due,
                    'collected' => $collected,
                    'method' => $i % 3 === 2 ? 'GCash' : 'Cash',
                    'status' => $collected >= $due ? 'Paid' : ($collected > 0 ? 'Partial' : 'Pending'),
                ];
            }

            $cursor += $count;

            $out[] = [
                'center' => $c,
                'rows' => $rows,
                'due_total' => array_sum(array_column($rows, 'due')),
                'collected_total' => array_sum(array_column($rows, 'collected')),
            ];
        }

        return $out;
    }

    public static function repayments(): array
    {
        $methods = ['Cash', 'GCash', 'Bank Transfer', 'Cash', 'Cash', 'GCash'];
        $receivers = ['Arnel P. Bacani', 'Grace M. Villamor', 'Dennis R. Alonzo', 'Cashier – Liza M. Fajardo'];
        $statuses = ['Posted', 'Posted', 'Posted', 'Pending', 'Posted', 'Failed'];

        return collect(self::members())->take(24)->values()->map(fn ($m, $i) => [
            'or_no' => 'OR-2026-'.str_pad((string) (84501 + $i * 3), 7, '0', STR_PAD_LEFT),
            'date' => sprintf('2026-09-%02d', max(1, 9 - intdiv($i, 4))),
            'time' => sprintf('%02d:%02d', 8 + ($i % 9), ($i * 7) % 60),
            'member' => $m['name'],
            'member_id' => $m['id'],
            'center' => $m['center'],
            'loan_id' => 'LN-2026-'.str_pad((string) (7001 + $i), 4, '0', STR_PAD_LEFT),
            'amount' => round(480 + (($i * 419) % 3600), 2),
            'method' => $methods[$i % count($methods)],
            'received_by' => $receivers[$i % count($receivers)],
            'status' => $statuses[$i % count($statuses)],
        ])->all();
    }

    public static function delinquentAccounts(): array
    {
        return collect(self::members())->take(16)->values()->map(function ($m, $i) {
            $dpd = 3 + (($i * 23) % 160);

            return [
                'loan_id' => 'LN-2026-'.str_pad((string) (7301 + $i), 4, '0', STR_PAD_LEFT),
                'member' => $m['name'],
                'member_id' => $m['id'],
                'center' => $m['center'],
                'officer' => $m['officer'],
                'outstanding' => round(6200 + (($i * 3117) % 58000), 2),
                'amount_overdue' => round(680 + (($i * 917) % 9400), 2),
                'dpd' => $dpd,
                'bucket' => self::bucketFor($dpd),
                'last_payment' => sprintf('2026-0%d-%02d', 4 + ($i % 5), 2 + ($i % 25)),
                'status' => $dpd > 90 ? 'Defaulted' : 'Overdue',
                'reminders_sent' => $i % 4,
            ];
        })->sortByDesc('dpd')->values()->all();
    }

    public static function bucketFor(int $dpd): string
    {
        return match (true) {
            $dpd <= 0 => 'Current',
            $dpd <= 30 => '1-30 days',
            $dpd <= 60 => '31-60 days',
            $dpd <= 90 => '61-90 days',
            default => '90+ days',
        };
    }

    public static function parBuckets(): array
    {
        return [
            ['label' => 'Current', 'amount' => 118420000, 'accounts' => 1_912, 'color' => Format::PAR_RAMP[0]],
            ['label' => '1-30 days', 'amount' => 6840000, 'accounts' => 214, 'color' => Format::PAR_RAMP[1]],
            ['label' => '31-60 days', 'amount' => 3120000, 'accounts' => 96, 'color' => Format::PAR_RAMP[2]],
            ['label' => '61-90 days', 'amount' => 1740000, 'accounts' => 51, 'color' => Format::PAR_RAMP[3]],
            ['label' => '90+ days', 'amount' => 2620000, 'accounts' => 73, 'color' => Format::PAR_RAMP[4]],
        ];
    }

    public static function penalties(): array
    {
        return collect(self::members())->take(14)->values()->map(fn ($m, $i) => [
            'id' => 'PEN-'.str_pad((string) (2001 + $i), 4, '0', STR_PAD_LEFT),
            'loan_id' => 'LN-2026-'.str_pad((string) (7301 + $i), 4, '0', STR_PAD_LEFT),
            'member' => $m['name'],
            'center' => $m['center'],
            'missed_due' => sprintf('2026-08-%02d', 2 + ($i % 26)),
            'days_late' => 4 + (($i * 9) % 70),
            'base_amount' => round(700 + (($i * 611) % 4200), 2),
            'penalty_rate' => 2.0,
            'accrued' => round(28 + (($i * 47) % 640), 2),
            'status' => $i % 6 === 4 ? 'Waived' : 'Pending',
        ])->all();
    }

    public static function restructuringRequests(): array
    {
        $reasons = ['Typhoon damage to store inventory', 'Prolonged illness of borrower', 'Loss of primary income source', 'Crop failure – delayed harvest', 'Business relocation'];

        return collect(self::members())->take(7)->values()->map(fn ($m, $i) => [
            'id' => 'RST-'.str_pad((string) (401 + $i), 4, '0', STR_PAD_LEFT),
            'loan_id' => 'LN-2026-'.str_pad((string) (7401 + $i), 4, '0', STR_PAD_LEFT),
            'member' => $m['name'],
            'center' => $m['center'],
            'outstanding' => round(14200 + (($i * 5311) % 46000), 2),
            'old_term' => 12,
            'new_term' => 18 + ($i % 3) * 3,
            'old_amort' => round(1840 + (($i * 233) % 900), 2),
            'new_amort' => round(1180 + (($i * 151) % 600), 2),
            'reason' => $reasons[$i % count($reasons)],
            'requested_on' => sprintf('2026-08-%02d', 6 + ($i * 3)),
            'status' => ['Under Review', 'Approved', 'Submitted', 'Under Review', 'Rejected'][$i % 5],
        ])->all();
    }

    public static function officerPerformance(): array
    {
        $rows = [
            ['Grace M. Villamor', 'Malolos Main Branch', 148, 1_420_000, 1_398_400, 0.9],
            ['Arnel P. Bacani', 'Malolos Main Branch', 162, 1_580_000, 1_501_000, 1.7],
            ['Maricel T. Ordoñez', 'San Jose del Monte Branch', 171, 1_640_000, 1_492_400, 3.1],
            ['Dennis R. Alonzo', 'Sta. Maria Branch', 134, 1_290_000, 1_134_720, 4.4],
            ['Jomar S. Pineda', 'Baliuag Branch', 109, 1_040_000, 872_560, 6.2],
        ];

        return collect($rows)->map(fn ($r, $i) => [
            'rank' => $i + 1,
            'officer' => $r[0],
            'branch' => $r[1],
            'accounts' => $r[2],
            'target' => $r[3],
            'collected' => $r[4],
            'efficiency' => round($r[4] / $r[3] * 100, 1),
            'par_contribution' => $r[5],
        ])->all();
    }

    // ---------------------------------------------------------------- savings

    public static function savingsAccounts(): array
    {
        $products = ['Regular Savings', 'Kabataan Savings', 'Time Deposit (6mo)', 'Regular Savings', 'Christmas Savings'];
        $statuses = ['Active', 'Active', 'Active', 'Dormant', 'Active', 'Closed'];

        return collect(self::members())->take(26)->values()->map(fn ($m, $i) => [
            'account_no' => 'SA-'.str_pad((string) (30001 + $i * 4), 6, '0', STR_PAD_LEFT),
            'member' => $m['name'],
            'member_id' => $m['id'],
            'center' => $m['center'],
            'branch' => $m['branch'],
            'product' => $products[$i % count($products)],
            'rate' => [1.5, 2.0, 3.25, 1.5, 1.75][$i % 5],
            'balance' => round(820 + (($i * 2731) % 74000), 2),
            'last_txn' => sprintf('2026-09-%02d', max(1, 9 - ($i % 9))),
            'opened' => sprintf('20%02d-%02d-%02d', 19 + ($i % 7), 1 + ($i % 12), 3 + ($i % 25)),
            'status' => $statuses[$i % count($statuses)],
        ])->all();
    }

    public static function savingsLedger(string $accountNo): array
    {
        $types = ['Deposit', 'Withdrawal', 'Deposit', 'Interest', 'Deposit', 'Withdrawal'];
        $balance = 4200.00;
        $rows = [];

        for ($i = 0; $i < 14; $i++) {
            $type = $types[$i % count($types)];
            $amount = $type === 'Interest'
                ? round($balance * 0.0125, 2)
                : round(220 + (($i * 683) % 4200), 2);

            $balance = $type === 'Withdrawal' ? $balance - $amount : $balance + $amount;

            $rows[] = [
                'date' => sprintf('2026-%02d-%02d', 3 + intdiv($i, 3), 4 + ($i * 2) % 26),
                'or_no' => $type === 'Interest' ? '—' : 'OR-2026-'.str_pad((string) (81001 + $i * 5), 7, '0', STR_PAD_LEFT),
                'type' => $type,
                'description' => match ($type) {
                    'Interest' => 'Quarterly interest credit',
                    'Withdrawal' => 'Over-the-counter withdrawal',
                    default => 'Center meeting deposit',
                },
                'debit' => $type === 'Withdrawal' ? $amount : 0.0,
                'credit' => $type === 'Withdrawal' ? 0.0 : $amount,
                'balance' => round($balance, 2),
                'teller' => ['Liza M. Fajardo', 'Ramon B. Aguilar'][$i % 2],
            ];
        }

        return array_reverse($rows);
    }

    public static function todaysTransactions(): array
    {
        $types = ['Deposit', 'Deposit', 'Withdrawal', 'Deposit', 'Withdrawal', 'Deposit'];

        return collect(self::members())->take(9)->values()->map(fn ($m, $i) => [
            'time' => sprintf('%02d:%02d', 8 + intdiv($i, 2), ($i * 13) % 60),
            'or_no' => 'OR-2026-'.str_pad((string) (84901 + $i * 2), 7, '0', STR_PAD_LEFT),
            'account_no' => 'SA-'.str_pad((string) (30001 + $i * 4), 6, '0', STR_PAD_LEFT),
            'member' => $m['name'],
            'type' => $types[$i % count($types)],
            'amount' => round(300 + (($i * 941) % 5200), 2),
            'method' => $i % 3 === 1 ? 'GCash' : 'Cash',
            'status' => 'Posted',
        ])->all();
    }

    public static function shareCapital(): array
    {
        return collect(self::members())->take(20)->values()->map(fn ($m, $i) => [
            'member_id' => $m['id'],
            'member' => $m['name'],
            'center' => $m['center'],
            'shares' => 20 + (($i * 7) % 180),
            'par_value' => 100.00,
            'total_capital' => (20 + (($i * 7) % 180)) * 100.00,
            'last_contribution' => sprintf('2026-0%d-%02d', 6 + ($i % 4), 2 + ($i % 26)),
            'last_amount' => round(200 + (($i % 9) * 100), 2),
            'status' => $i % 9 === 7 ? 'Inactive' : 'Active',
        ])->all();
    }

    public static function blotter(): array
    {
        $beginning = 85000.00;
        $collections = 412860.00;
        $disbursements = 268000.00;
        $deposits = 96420.50;
        $withdrawals = 41380.00;
        $expected = $beginning + $collections + $deposits - $disbursements - $withdrawals;
        $actual = 284880.50;

        return [
            'date' => '2026-09-09',
            'teller' => 'Liza M. Fajardo',
            'branch' => 'Malolos Main Branch',
            'beginning' => $beginning,
            'collections' => $collections,
            'disbursements' => $disbursements,
            'deposits' => $deposits,
            'withdrawals' => $withdrawals,
            'expected' => $expected,
            'actual' => $actual,
            'variance' => round($actual - $expected, 2),
        ];
    }

    public static function denominations(): array
    {
        return [
            ['label' => '₱1,000', 'value' => 1000, 'count' => 184],
            ['label' => '₱500', 'value' => 500, 'count' => 126],
            ['label' => '₱200', 'value' => 200, 'count' => 41],
            ['label' => '₱100', 'value' => 100, 'count' => 208],
            ['label' => '₱50', 'value' => 50, 'count' => 132],
            ['label' => '₱20', 'value' => 20, 'count' => 96],
            ['label' => '₱10 coin', 'value' => 10, 'count' => 74],
            ['label' => '₱5 coin', 'value' => 5, 'count' => 58],
            ['label' => '₱1 coin', 'value' => 1, 'count' => 110],
        ];
    }

    public static function interestPreview(): array
    {
        return collect(self::savingsAccounts())->take(18)->values()->map(function ($a, $i) {
            $days = 90;
            $interest = round($a['balance'] * ($a['rate'] / 100) * ($days / 365), 2);

            return [
                'account_no' => $a['account_no'],
                'member' => $a['member'],
                'product' => $a['product'],
                'balance' => $a['balance'],
                'rate' => $a['rate'],
                'days' => $days,
                'interest' => $interest,
                'status' => $a['status'] === 'Active' ? 'Pending' : 'Skipped',
            ];
        })->all();
    }

    // ---------------------------------------------------------------- admin

    public static function systemUsers(): array
    {
        return [
            ['id' => 'USR-001', 'name' => 'Editha C. Ramirez', 'email' => 'admin@microfleet.local', 'role' => 'Super Admin', 'branch' => 'All Depots', 'last_login' => '2026-09-23 08:15', 'status' => 'Active'],
            ['id' => 'USR-002', 'name' => 'Teresita G. Gonzales', 'email' => 'manager@microfleet.local', 'role' => 'Fleet Manager', 'branch' => 'Malolos Main Depot', 'last_login' => '2026-09-23 07:42', 'status' => 'Active'],
            ['id' => 'USR-003', 'name' => 'Ramon B. Aguilar', 'email' => 'dispatcher@microfleet.local', 'role' => 'Dispatcher', 'branch' => 'Malolos Main Depot', 'last_login' => '2026-09-23 07:10', 'status' => 'Active'],
            ['id' => 'USR-004', 'name' => 'Lito M. Ramos', 'email' => 'driver@microfleet.local', 'role' => 'Driver', 'branch' => 'Malolos Main Depot', 'last_login' => '2026-09-23 06:55', 'status' => 'Active'],
            ['id' => 'USR-005', 'name' => 'Nora C. Cruz', 'email' => 'nora.cruz@microfleet.local', 'role' => 'Driver', 'branch' => 'Malolos Main Depot', 'last_login' => '2026-09-22 16:20', 'status' => 'Active'],
            ['id' => 'USR-006', 'name' => 'Rafael P. Santos', 'email' => 'rafael.santos@microfleet.local', 'role' => 'Driver', 'branch' => 'Malolos Main Depot', 'last_login' => '2026-09-22 15:40', 'status' => 'Active'],
            ['id' => 'USR-008', 'name' => 'Maricel T. Ordonez', 'email' => 'maricel.ordonez@microfleet.local', 'role' => 'Driver', 'branch' => 'Malolos Main Depot', 'last_login' => '2026-09-22 14:20', 'status' => 'Active'],
        ];
    }

    public static function roles(): array
    {
        return [
            'Super Admin',
            'Fleet Manager',
            'Dispatcher',
            'Driver',
        ];
    }

    /** Default permission grid: role => module.sub => [view, create, edit, delete] */
    public static function permissionDefaults(): array
    {
        return [
            'Super Admin' => ['*' => ['view', 'create', 'edit', 'delete']],
            'Fleet Manager' => [
                'fleet-vehicle' => ['view', 'create', 'edit', 'delete'],
                'reservation-dispatch' => ['view', 'create', 'edit', 'delete'],
                'driver-trip' => ['view', 'create', 'edit', 'delete'],
                'fuel' => ['view', 'create', 'edit', 'delete'],
                'cost-optimization' => ['view'],
                'route-optimization' => ['view', 'create', 'edit', 'delete'],
            ],
            'Dispatcher' => [
                'reservation-dispatch' => ['view', 'create', 'edit'],
                'fuel' => ['view', 'create', 'edit'],
            ],
            'Driver' => [
                'fleet-vehicle' => ['view'],
                'driver-trip' => ['view'],
            ],
        ];
    }

    public static function auditLog(): array
    {
        $entries = [
            ['Editha C. Ramirez', 'Updated role permissions', 'Administration', 'Users & Roles', '203.177.42.18', 'Loan Officer: collections.delete false → false; loans.create false → true'],
            ['Liza M. Fajardo', 'Posted collection batch', 'Collections', 'Daily Collection Sheet', '192.168.10.24', '17 receipts, total ₱24,860.00 — Center 01, 02, 03'],
            ['Teresita G. Gonzales', 'Approved loan application', 'Loan Management', 'Credit Assessment', '192.168.10.11', 'APP-2026-3004 · ₱45,000.00 · Negosyo Plus'],
            ['Arnel P. Bacani', 'Created member', 'Membership', 'Member Registration', '192.168.10.55', 'MBR-01033 Melanie S. Sionil — Center 02'],
            ['Ramon B. Aguilar', 'Exported report', 'Administration', 'Reports Center', '203.177.42.90', 'Portfolio at Risk — Aug 2026 — XLSX'],
            ['Liza M. Fajardo', 'Closed day', 'Savings & Treasury', 'Teller Blotter', '192.168.10.24', 'Variance ₱0.00 · Expected ₱284,900.50'],
            ['Grace M. Villamor', 'Waived penalty', 'Collections', 'Penalties & Restructuring', '192.168.10.61', 'PEN-2007 ₱312.00 — approved by T. Gonzales'],
            ['Benigno L. Ocampo', 'Released disbursement batch', 'Loan Management', 'Disbursements', '192.168.20.12', '4 loans · ₱182,000.00 · GCash'],
            ['Editha C. Ramirez', 'Deactivated user', 'Administration', 'Users & Roles', '203.177.42.18', 'USR-006 Dennis R. Alonzo → Inactive'],
            ['Maricel T. Ordoñez', 'Verified KYC document', 'Membership', 'KYC & Documents', '192.168.30.40', 'DOC-05012 Valid ID (PhilSys) → Verified'],
            ['System', 'Interest posting run', 'Savings & Treasury', 'Interest Posting', '127.0.0.1', 'Q3 2026 · 1,284 accounts · ₱96,412.30'],
            ['Teresita G. Gonzales', 'Rejected loan application', 'Loan Management', 'Credit Assessment', '192.168.10.11', 'APP-2026-3009 — capacity-to-pay below 25% threshold'],
        ];

        return collect($entries)->map(fn ($e, $i) => [
            'id' => 'AUD-'.str_pad((string) (91001 + $i), 6, '0', STR_PAD_LEFT),
            'timestamp' => sprintf('2026-09-%02d %02d:%02d:%02d', 9 - intdiv($i, 3), 7 + ($i % 11), ($i * 17) % 60, ($i * 7) % 60),
            'actor' => $e[0],
            'action' => $e[1],
            'module' => $e[2],
            'submodule' => $e[3],
            'ip' => $e[4],
            'detail' => $e[5],
        ])->all();
    }

    public static function notifications(): array
    {
        return [
            ['icon' => 'alert-triangle', 'tone' => 'danger', 'title' => '9 accounts crossed 30 days past due', 'body' => 'Center 05 and Center 08 · review needed', 'time' => '12m ago'],
            ['icon' => 'file-check', 'tone' => 'info', 'title' => '4 applications awaiting your approval', 'body' => 'Total requested ₱182,500.00', 'time' => '1h ago'],
            ['icon' => 'banknote', 'tone' => 'success', 'title' => 'Collections posted for Center 03', 'body' => '₱18,420.00 by Grace M. Villamor', 'time' => '2h ago'],
            ['icon' => 'clock', 'tone' => 'warning', 'title' => 'Teller blotter not yet closed', 'body' => 'Malolos Main Branch · 08 Sep 2026', 'time' => 'Yesterday'],
        ];
    }

    public static function recentActivity(): array
    {
        return [
            ['icon' => 'banknote', 'tone' => 'success', 'actor' => 'Grace M. Villamor', 'text' => 'posted 14 collections for Center 03 – Brgy. Poblacion', 'meta' => '₱18,420.00', 'time' => '08:42 AM'],
            ['icon' => 'user-plus', 'tone' => 'info', 'actor' => 'Arnel P. Bacani', 'text' => 'registered a new member, Melanie S. Sionil', 'meta' => 'MBR-01033', 'time' => '08:15 AM'],
            ['icon' => 'file-check', 'tone' => 'success', 'actor' => 'Teresita G. Gonzales', 'text' => 'approved loan application APP-2026-3004', 'meta' => '₱45,000.00', 'time' => '07:58 AM'],
            ['icon' => 'alert-triangle', 'tone' => 'danger', 'actor' => 'System', 'text' => 'flagged LN-2026-7310 as 90+ days past due', 'meta' => 'Center 08', 'time' => '07:30 AM'],
            ['icon' => 'wallet', 'tone' => 'info', 'actor' => 'Liza M. Fajardo', 'text' => 'released 4 disbursements via GCash', 'meta' => '₱182,000.00', 'time' => 'Yesterday'],
            ['icon' => 'shield-check', 'tone' => 'success', 'actor' => 'Maricel T. Ordoñez', 'text' => 'verified 6 KYC documents', 'meta' => 'Center 07', 'time' => 'Yesterday'],
        ];
    }

    public static function loansDueToday(): array
    {
        return collect(self::members())->take(8)->values()->map(fn ($m, $i) => [
            'loan_id' => 'LN-2026-'.str_pad((string) (7001 + $i), 4, '0', STR_PAD_LEFT),
            'member' => $m['name'],
            'member_id' => $m['id'],
            'center' => $m['center'],
            'officer' => $m['officer'],
            'amount_due' => round(520 + (($i * 383) % 3100), 2),
            'outstanding' => round(7400 + (($i * 2711) % 52000), 2),
            'dpd' => $i % 4 === 3 ? 2 + $i : 0,
            'status' => $i % 4 === 3 ? 'Overdue' : 'Due Soon',
        ])->all();
    }

    public static function pendingApprovals(): array
    {
        return collect(self::loanApplications())->where('stage', 'Under Review')->take(4)->values()->map(fn ($a) => [
            'id' => $a['id'],
            'member' => $a['member'],
            'center' => $a['center'],
            'amount' => $a['amount'],
            'product' => $a['product'],
            'term' => $a['term'],
            'officer' => $a['officer'],
            'days' => $a['days_in_stage'],
        ])->all();
    }

    // ---------------------------------------------------------------- chart series

    public static function months(): array
    {
        return ['Oct 25', 'Nov 25', 'Dec 25', 'Jan 26', 'Feb 26', 'Mar 26', 'Apr 26', 'May 26', 'Jun 26', 'Jul 26', 'Aug 26', 'Sep 26'];
    }

    public static function disbursementsVsCollections(): array
    {
        return [
            'labels' => self::months(),
            'disbursements' => [4_120_000, 4_680_000, 5_940_000, 3_820_000, 4_240_000, 4_910_000, 5_180_000, 4_760_000, 5_320_000, 5_680_000, 6_140_000, 5_420_000],
            'collections' => [3_840_000, 4_120_000, 4_960_000, 4_380_000, 4_020_000, 4_540_000, 4_820_000, 4_640_000, 5_010_000, 5_240_000, 5_610_000, 5_180_000],
        ];
    }

    public static function portfolioGrowth(): array
    {
        return [
            'labels' => self::months(),
            'portfolio' => [98_400_000, 101_200_000, 106_800_000, 108_100_000, 110_400_000, 113_900_000, 116_200_000, 118_800_000, 121_600_000, 125_100_000, 129_400_000, 132_740_000],
            'savings' => [38_200_000, 39_100_000, 42_800_000, 43_400_000, 44_100_000, 45_600_000, 46_300_000, 47_900_000, 49_200_000, 50_800_000, 52_400_000, 54_120_000],
        ];
    }

    public static function repaymentRateTrend(): array
    {
        return [
            'labels' => self::months(),
            'rate' => [96.2, 95.8, 94.9, 96.4, 97.1, 96.8, 95.9, 96.6, 97.3, 97.0, 96.4, 96.9],
        ];
    }

    public static function collectionEfficiencyTrend(): array
    {
        return [
            'labels' => self::months(),
            'series' => [
                ['label' => 'Malolos Main', 'data' => [97.1, 96.4, 95.8, 97.2, 98.0, 97.6, 96.9, 97.4, 98.1, 97.8, 97.2, 97.9]],
                ['label' => 'Sta. Maria', 'data' => [94.2, 93.8, 92.6, 94.8, 95.4, 95.0, 94.1, 94.9, 95.6, 95.2, 94.4, 95.1]],
                ['label' => 'San Jose del Monte', 'data' => [92.8, 92.1, 90.9, 93.4, 94.2, 93.7, 92.8, 93.6, 94.4, 93.9, 93.1, 93.8]],
            ],
        ];
    }

    public static function productMix(): array
    {
        return collect(self::loanProducts())->where('portfolio', '>', 0)->map(fn ($p) => [
            'label' => $p['name'],
            'value' => $p['portfolio'],
        ])->values()->all();
    }

    public static function disbursementByBranch(): array
    {
        return [
            'labels' => ['Malolos Main', 'Sta. Maria', 'SJDM', 'Baliuag', 'Plaridel'],
            'data' => [2_180_000, 1_240_000, 1_460_000, 780_000, 520_000],
        ];
    }

    public static function memberGrowth(): array
    {
        return [
            'labels' => self::months(),
            'new' => [42, 38, 51, 34, 40, 47, 52, 45, 49, 58, 61, 44],
            'exits' => [12, 9, 14, 11, 8, 13, 10, 12, 9, 15, 11, 8],
        ];
    }

    public static function groupPerformance(): array
    {
        return [
            'labels' => ['Apr 26', 'May 26', 'Jun 26', 'Jul 26', 'Aug 26', 'Sep 26'],
            'due' => [148_000, 152_000, 156_000, 161_000, 164_000, 168_000],
            'collected' => [142_000, 149_000, 151_400, 158_600, 157_800, 165_200],
        ];
    }

    // ---------------------------------------------------------------- misc

    public static function reportCards(): array
    {
        return [
            ['key' => 'par', 'icon' => 'alert-triangle', 'title' => 'Portfolio at Risk', 'desc' => 'Aging of outstanding balances by days past due, per branch and officer.', 'route' => 'admin.reports.par'],
            ['key' => 'aging', 'icon' => 'clock', 'title' => 'Aging Report', 'desc' => 'Bucketed receivables with roll-rate movement between periods.'],
            ['key' => 'disb', 'icon' => 'wallet', 'title' => 'Disbursement Summary', 'desc' => 'Releases by product, branch, and release method for any date range.'],
            ['key' => 'ceff', 'icon' => 'target', 'title' => 'Collection Efficiency', 'desc' => 'Amount collected against amount due, by center and loan officer.'],
            ['key' => 'savmov', 'icon' => 'piggy-bank', 'title' => 'Savings Movement', 'desc' => 'Deposits, withdrawals, and interest posted across savings products.'],
            ['key' => 'offperf', 'icon' => 'award', 'title' => 'Officer Performance', 'desc' => 'Targets versus collections with PAR contribution per loan officer.'],
            ['key' => 'growth', 'icon' => 'trending-up', 'title' => 'Member Growth', 'desc' => 'New, active, dormant, and exited members over time.'],
            ['key' => 'tb', 'icon' => 'calculator', 'title' => 'Trial Balance', 'desc' => 'General ledger balances for the selected accounting period.'],
        ];
    }

    public static function membershipReportCards(): array
    {
        return [
            ['key' => 'new-members', 'icon' => 'user-plus', 'title' => 'New Members', 'desc' => 'Registrations per period, broken down by center and recruiting officer.', 'value' => '44', 'meta' => 'this month'],
            ['key' => 'growth', 'icon' => 'trending-up', 'title' => 'Member Growth', 'desc' => 'Net movement of the member base across the last 12 months.', 'value' => '+8.4%', 'meta' => 'year to date'],
            ['key' => 'dormant', 'icon' => 'clock', 'title' => 'Dormant Members', 'desc' => 'Members with no loan or savings activity in 180 days.', 'value' => '112', 'meta' => 'as of today'],
            ['key' => 'by-center', 'icon' => 'map-pin', 'title' => 'Members by Center', 'desc' => 'Headcount and portfolio concentration per center.', 'value' => '8', 'meta' => 'active centers'],
            ['key' => 'retention', 'icon' => 'shield-check', 'title' => 'Retention', 'desc' => 'Repeat-borrower rate and average membership tenure.', 'value' => '87.2%', 'meta' => '12-month retention'],
        ];
    }
}
