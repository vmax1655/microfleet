<?php

namespace App\Livewire\Logistics;

use App\Models\Depot;
use App\Support\Rbac;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Depots and Yards')]
class Depots extends Component
{
    // Comprehensive GPS coordinate directory covering NCR (Metro Manila), Central Luzon, and Southern Luzon
    public static array $locationCoordinates = [
        // --- METRO MANILA (NCR) ---
        'Quezon City' => ['lat' => 14.6760410, 'lng' => 121.0437000, 'region' => 'Metro Manila'],
        'Manila' => ['lat' => 14.5995120, 'lng' => 120.9842190, 'region' => 'Metro Manila'],
        'City of Manila' => ['lat' => 14.5995120, 'lng' => 120.9842190, 'region' => 'Metro Manila'],
        'Caloocan' => ['lat' => 14.6507000, 'lng' => 120.9830000, 'region' => 'Metro Manila'],
        'North Caloocan' => ['lat' => 14.7566000, 'lng' => 121.0445000, 'region' => 'Metro Manila'],
        'South Caloocan' => ['lat' => 14.6416000, 'lng' => 120.9762000, 'region' => 'Metro Manila'],
        'Makati' => ['lat' => 14.5547290, 'lng' => 121.0244450, 'region' => 'Metro Manila'],
        'Taguig' => ['lat' => 14.5176180, 'lng' => 121.0508650, 'region' => 'Metro Manila'],
        'BGC' => ['lat' => 14.5507000, 'lng' => 121.0505000, 'region' => 'Metro Manila'],
        'Bonifacio Global City' => ['lat' => 14.5507000, 'lng' => 121.0505000, 'region' => 'Metro Manila'],
        'Pasig' => ['lat' => 14.5763770, 'lng' => 121.0851100, 'region' => 'Metro Manila'],
        'Parañaque' => ['lat' => 14.4793090, 'lng' => 121.0198210, 'region' => 'Metro Manila'],
        'Paranaque' => ['lat' => 14.4793090, 'lng' => 121.0198210, 'region' => 'Metro Manila'],
        'Las Piñas' => ['lat' => 14.4445460, 'lng' => 120.9938740, 'region' => 'Metro Manila'],
        'Las Pinas' => ['lat' => 14.4445460, 'lng' => 120.9938740, 'region' => 'Metro Manila'],
        'Muntinlupa' => ['lat' => 14.4081330, 'lng' => 121.0414660, 'region' => 'Metro Manila'],
        'Alabang' => ['lat' => 14.4225000, 'lng' => 121.0428000, 'region' => 'Metro Manila'],
        'Mandaluyong' => ['lat' => 14.5794440, 'lng' => 121.0358890, 'region' => 'Metro Manila'],
        'Marikina' => ['lat' => 14.6507000, 'lng' => 121.1029000, 'region' => 'Metro Manila'],
        'Pasay' => ['lat' => 14.5377780, 'lng' => 120.9997220, 'region' => 'Metro Manila'],
        'Valenzuela' => ['lat' => 14.7011110, 'lng' => 120.9830560, 'region' => 'Metro Manila'],
        'Malabon' => ['lat' => 14.6625000, 'lng' => 120.9566000, 'region' => 'Metro Manila'],
        'Navotas' => ['lat' => 14.6667000, 'lng' => 120.9417000, 'region' => 'Metro Manila'],
        'San Juan' => ['lat' => 14.6019440, 'lng' => 121.0355560, 'region' => 'Metro Manila'],
        'Pateros' => ['lat' => 14.5454000, 'lng' => 121.0686000, 'region' => 'Metro Manila'],

        // --- BULACAN ---
        'Malolos' => ['lat' => 14.8527390, 'lng' => 120.8160380, 'region' => 'Bulacan'],
        'Paombong' => ['lat' => 14.8315130, 'lng' => 120.7897030, 'region' => 'Bulacan'],
        'Calumpit' => ['lat' => 14.9168000, 'lng' => 120.7659000, 'region' => 'Bulacan'],
        'Baliuag' => ['lat' => 14.9536000, 'lng' => 120.9015000, 'region' => 'Bulacan'],
        'Baliwag' => ['lat' => 14.9536000, 'lng' => 120.9015000, 'region' => 'Bulacan'],
        'San Jose del Monte' => ['lat' => 14.8144000, 'lng' => 121.0453000, 'region' => 'Bulacan'],
        'SJDM' => ['lat' => 14.8144000, 'lng' => 121.0453000, 'region' => 'Bulacan'],
        'Meycauayan' => ['lat' => 14.7739000, 'lng' => 120.9544000, 'region' => 'Bulacan'],
        'Marilao' => ['lat' => 14.7578000, 'lng' => 120.9472000, 'region' => 'Bulacan'],
        'Bocaue' => ['lat' => 14.7981000, 'lng' => 120.9269000, 'region' => 'Bulacan'],
        'Guiguinto' => ['lat' => 14.8306000, 'lng' => 120.8794000, 'region' => 'Bulacan'],
        'Plaridel' => ['lat' => 14.8872000, 'lng' => 120.8572000, 'region' => 'Bulacan'],
        'Pulilan' => ['lat' => 14.9018000, 'lng' => 120.8504000, 'region' => 'Bulacan'],
        'Hagonoy' => ['lat' => 14.8344000, 'lng' => 120.7328000, 'region' => 'Bulacan'],
        'Bulakan' => ['lat' => 14.7936000, 'lng' => 120.8783000, 'region' => 'Bulacan'],
        'Santa Maria' => ['lat' => 14.8183000, 'lng' => 120.9583000, 'region' => 'Bulacan'],
        'Pandi' => ['lat' => 14.8667000, 'lng' => 120.9500000, 'region' => 'Bulacan'],
        'Balagtas' => ['lat' => 14.8167000, 'lng' => 120.9083000, 'region' => 'Bulacan'],
        'Bustos' => ['lat' => 14.9536000, 'lng' => 120.9167000, 'region' => 'Bulacan'],
        'Angat' => ['lat' => 14.9317000, 'lng' => 121.0317000, 'region' => 'Bulacan'],
        'Norzagaray' => ['lat' => 14.9150000, 'lng' => 121.0442000, 'region' => 'Bulacan'],
        'San Rafael' => ['lat' => 14.9667000, 'lng' => 120.9333000, 'region' => 'Bulacan'],
        'San Ildefonso' => ['lat' => 15.0789000, 'lng' => 120.9404000, 'region' => 'Bulacan'],
        'San Miguel' => ['lat' => 15.1436000, 'lng' => 120.9767000, 'region' => 'Bulacan'],
        'Obando' => ['lat' => 14.7100000, 'lng' => 120.9381000, 'region' => 'Bulacan'],
        'Doña Remedios Trinidad' => ['lat' => 14.9833000, 'lng' => 121.0667000, 'region' => 'Bulacan'],
        'DRT' => ['lat' => 14.9833000, 'lng' => 121.0667000, 'region' => 'Bulacan'],

        // --- PAMPANGA ---
        'San Fernando' => ['lat' => 15.0298000, 'lng' => 120.6896000, 'region' => 'Pampanga'],
        'Angeles City' => ['lat' => 15.1472000, 'lng' => 120.5847000, 'region' => 'Pampanga'],
        'Angeles' => ['lat' => 15.1472000, 'lng' => 120.5847000, 'region' => 'Pampanga'],
        'Clark' => ['lat' => 15.1856000, 'lng' => 120.5367000, 'region' => 'Pampanga'],
        'Clark Freeport' => ['lat' => 15.1856000, 'lng' => 120.5367000, 'region' => 'Pampanga'],
        'Mabalacat' => ['lat' => 15.2222000, 'lng' => 120.5739000, 'region' => 'Pampanga'],
        'Guagua' => ['lat' => 14.9667000, 'lng' => 120.6333000, 'region' => 'Pampanga'],
        'Lubao' => ['lat' => 14.9392000, 'lng' => 120.5986000, 'region' => 'Pampanga'],
        'Apalit' => ['lat' => 14.9583000, 'lng' => 120.7583000, 'region' => 'Pampanga'],
        'Mexico' => ['lat' => 15.0667000, 'lng' => 120.7167000, 'region' => 'Pampanga'],
        'Arayat' => ['lat' => 15.1500000, 'lng' => 120.7667000, 'region' => 'Pampanga'],
        'Porac' => ['lat' => 15.0711000, 'lng' => 120.5425000, 'region' => 'Pampanga'],
        'Floridablanca' => ['lat' => 14.9739000, 'lng' => 120.5317000, 'region' => 'Pampanga'],

        // --- RIZAL ---
        'Antipolo' => ['lat' => 14.5842000, 'lng' => 121.1764000, 'region' => 'Rizal'],
        'Cainta' => ['lat' => 14.5772000, 'lng' => 121.1214000, 'region' => 'Rizal'],
        'Taytay' => ['lat' => 14.5583000, 'lng' => 121.1319000, 'region' => 'Rizal'],
        'San Mateo' => ['lat' => 14.6961000, 'lng' => 121.1219000, 'region' => 'Rizal'],
        'Rodriguez' => ['lat' => 14.7333000, 'lng' => 121.1500000, 'region' => 'Rizal'],
        'Montalban' => ['lat' => 14.7333000, 'lng' => 121.1500000, 'region' => 'Rizal'],
        'Angono' => ['lat' => 14.5250000, 'lng' => 121.1539000, 'region' => 'Rizal'],
        'Binangonan' => ['lat' => 14.4647000, 'lng' => 121.1925000, 'region' => 'Rizal'],

        // --- CAVITE ---
        'Bacoor' => ['lat' => 14.4624000, 'lng' => 120.9650000, 'region' => 'Cavite'],
        'Imus' => ['lat' => 14.4296000, 'lng' => 120.9367000, 'region' => 'Cavite'],
        'Dasmariñas' => ['lat' => 14.3294000, 'lng' => 120.9367000, 'region' => 'Cavite'],
        'Dasmarinas' => ['lat' => 14.3294000, 'lng' => 120.9367000, 'region' => 'Cavite'],
        'General Trias' => ['lat' => 14.3869000, 'lng' => 120.8814000, 'region' => 'Cavite'],
        'Tagaytay' => ['lat' => 14.1153000, 'lng' => 120.9621000, 'region' => 'Cavite'],
        'Cavite City' => ['lat' => 14.4833000, 'lng' => 120.9000000, 'region' => 'Cavite'],
        'Silang' => ['lat' => 14.2319000, 'lng' => 120.9744000, 'region' => 'Cavite'],
        'Kawit' => ['lat' => 14.4444000, 'lng' => 120.9036000, 'region' => 'Cavite'],
        'Carmona' => ['lat' => 14.3167000, 'lng' => 121.0500000, 'region' => 'Cavite'],

        // --- LAGUNA ---
        'Santa Rosa' => ['lat' => 14.3122000, 'lng' => 121.1114000, 'region' => 'Laguna'],
        'Sta. Rosa' => ['lat' => 14.3122000, 'lng' => 121.1114000, 'region' => 'Laguna'],
        'Calamba' => ['lat' => 14.2117000, 'lng' => 121.1656000, 'region' => 'Laguna'],
        'Biñan' => ['lat' => 14.3333000, 'lng' => 121.0833000, 'region' => 'Laguna'],
        'Binan' => ['lat' => 14.3333000, 'lng' => 121.0833000, 'region' => 'Laguna'],
        'Cabuyao' => ['lat' => 14.2786000, 'lng' => 121.1247000, 'region' => 'Laguna'],
        'San Pedro' => ['lat' => 14.3583000, 'lng' => 121.0500000, 'region' => 'Laguna'],
        'Los Baños' => ['lat' => 14.1706000, 'lng' => 121.2431000, 'region' => 'Laguna'],
        'San Pablo' => ['lat' => 14.0683000, 'lng' => 121.3256000, 'region' => 'Laguna'],

        // --- TARLAC, BATAAN, NUEVA ECIJA, ZAMBALES, BATANGAS, NORTH LUZON ---
        'Tarlac City' => ['lat' => 15.4802000, 'lng' => 120.5979000, 'region' => 'Tarlac'],
        'Tarlac' => ['lat' => 15.4802000, 'lng' => 120.5979000, 'region' => 'Tarlac'],
        'Cabanatuan' => ['lat' => 15.4864000, 'lng' => 120.9697000, 'region' => 'Nueva Ecija'],
        'Cabanatuan City' => ['lat' => 15.4864000, 'lng' => 120.9697000, 'region' => 'Nueva Ecija'],
        'Gapan' => ['lat' => 15.3083000, 'lng' => 120.9472000, 'region' => 'Nueva Ecija'],
        'Balanga' => ['lat' => 14.6806000, 'lng' => 120.5406000, 'region' => 'Bataan'],
        'Balanga City' => ['lat' => 14.6806000, 'lng' => 120.5406000, 'region' => 'Bataan'],
        'Mariveles' => ['lat' => 14.4333000, 'lng' => 120.4833000, 'region' => 'Bataan'],
        'Subic' => ['lat' => 14.8833000, 'lng' => 120.2333000, 'region' => 'Zambales'],
        'Olongapo' => ['lat' => 14.8386000, 'lng' => 120.2842000, 'region' => 'Zambales'],
        'Olongapo City' => ['lat' => 14.8386000, 'lng' => 120.2842000, 'region' => 'Zambales'],
        'Batangas City' => ['lat' => 13.7565000, 'lng' => 121.0583000, 'region' => 'Batangas'],
        'Lipa' => ['lat' => 13.9419000, 'lng' => 121.1644000, 'region' => 'Batangas'],
        'Lipa City' => ['lat' => 13.9419000, 'lng' => 121.1644000, 'region' => 'Batangas'],
        'Lucena' => ['lat' => 13.9314000, 'lng' => 121.6172000, 'region' => 'Quezon'],
        'Baguio' => ['lat' => 16.4023000, 'lng' => 120.5960000, 'region' => 'Benguet'],
        'Baguio City' => ['lat' => 16.4023000, 'lng' => 120.5960000, 'region' => 'Benguet'],
        'Dagupan' => ['lat' => 16.0433000, 'lng' => 120.3342000, 'region' => 'Pangasinan'],
        'Urdaneta' => ['lat' => 15.9761000, 'lng' => 120.5711000, 'region' => 'Pangasinan'],
    ];

    // New depot form properties
    public string $name = '';
    public string $address = '';
    public float|string $latitude = '14.8527390';
    public float|string $longitude = '120.8160380';
    public string $status = 'active';

    public ?string $bannerMessage = null;

    /**
     * Auto-detect coordinates when the user enters or changes the City/municipality address.
     */
    public function updatedAddress(string $value): void
    {
        $this->detectCoordinates($value);
    }

    /**
     * Auto-detect coordinates or municipality when user types the Depot name (e.g. "Quezon City Depot").
     */
    public function updatedName(string $value): void
    {
        if (empty($this->address)) {
            $this->detectCoordinates($value, updateAddress: true);
        }
    }

    /**
     * Find best matching municipality/city across NCR and Luzon and auto-fill latitude and longitude.
     */
    private function detectCoordinates(string $text, bool $updateAddress = false): void
    {
        $textLower = strtolower(trim($text));
        if (empty($textLower)) {
            return;
        }

        // Extract primary city segment before comma if present (e.g. "Taguig, Metro Manila" -> "taguig")
        $primaryText = strtolower(trim(explode(',', $textLower)[0]));

        $places = self::$locationCoordinates;
        uksort($places, fn ($a, $b) => strlen($b) <=> strlen($a));

        // 1. Check against primary city text first
        foreach ($places as $place => $data) {
            $pLower = strtolower($place);
            if ($pLower === 'bulakan' && ! str_contains($primaryText, 'bulakan')) {
                continue;
            }
            if ($pLower === 'manila' && str_contains($primaryText, 'metro manila') && ! preg_match('/\b(city of manila|manila city|manila)\b/', $primaryText)) {
                continue;
            }
            if (str_contains($primaryText, $pLower)) {
                $this->latitude = number_format($data['lat'], 7, '.', '');
                $this->longitude = number_format($data['lng'], 7, '.', '');

                if ($updateAddress && empty($this->address)) {
                    $this->address = $place.', '.($data['region'] ?? 'Luzon');
                }
                return;
            }
        }

        // 2. Fallback check on entire text
        foreach ($places as $place => $data) {
            $pLower = strtolower($place);
            if ($pLower === 'bulakan' && ! str_contains($textLower, 'bulakan')) {
                continue;
            }
            if ($pLower === 'manila' && ! preg_match('/\b(city of manila|manila)\b/', $textLower)) {
                continue;
            }
            if (str_contains($textLower, $pLower)) {
                $this->latitude = number_format($data['lat'], 7, '.', '');
                $this->longitude = number_format($data['lng'], 7, '.', '');

                if ($updateAddress && empty($this->address)) {
                    $this->address = $place.', '.($data['region'] ?? 'Luzon');
                }
                return;
            }
        }
    }

    public function saveDepot(): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'logistics.depots', 'create'), 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:120', 'unique:depots,name'],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'status' => ['required', 'in:active,inactive'],
        ], [
            'name.required' => 'Please enter the depot or staging yard name.',
            'name.unique' => 'A depot with this name already exists.',
            'address.required' => 'Please enter the city or location address.',
            'latitude.required' => 'Please enter latitude coordinates.',
            'longitude.required' => 'Please enter longitude coordinates.',
        ]);

        $depot = Depot::create([
            'name' => trim($data['name']),
            'address' => trim($data['address']),
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
            'status' => $data['status'],
        ]);

        // Auto-provision standard operational routes for new depot
        $abbr = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $depot->name), 0, 3));
        if (strlen($abbr) < 2) $abbr = 'DEP';
        $cleanCity = explode(',', $depot->address ?: $depot->name)[0];

        $defaultRoutes = [
            [
                'code' => "RTE-{$abbr}-01",
                'name' => "{$depot->name} - {$cleanCity} Microfinance Center",
                'center' => "CTR-{$abbr}-01",
                'dest' => "{$cleanCity} Lending & Collection Center 01",
                'dist' => 8.5,
                'dur' => 25,
            ],
            [
                'code' => "RTE-{$abbr}-02",
                'name' => "{$depot->name} - {$cleanCity} Field Office",
                'center' => "CTR-{$abbr}-02",
                'dest' => "{$cleanCity} Field Barangay Office 02",
                'dist' => 14.2,
                'dur' => 35,
            ],
            [
                'code' => "RTE-{$abbr}-MAL",
                'name' => "{$depot->name} - Malolos Main Depot Transfer",
                'center' => "DEP-MAL",
                'dest' => "Malolos Main Depot (Inter-Depot)",
                'dist' => 35.0,
                'dur' => 50,
            ],
        ];

        foreach ($defaultRoutes as $dr) {
            \App\Models\TransportRoute::firstOrCreate(
                ['route_code' => $dr['code']],
                [
                    'origin_depot_id' => $depot->id,
                    'name' => $dr['name'],
                    'center_code' => $dr['center'],
                    'destination_name' => $dr['dest'],
                    'destination_latitude' => $depot->latitude,
                    'destination_longitude' => $depot->longitude,
                    'planned_distance_km' => $dr['dist'],
                    'estimated_duration_minutes' => $dr['dur'],
                    'road_profile' => 'mixed',
                    'status' => 'active',
                ]
            );
        }

        $this->bannerMessage = "Staging Depot '{$depot->name}' created successfully with operational routes!";
        $this->dispatch('close-modal');

        $this->dispatch('depot-created', depot: [
            'id' => $depot->id,
            'name' => $depot->name,
            'address' => $depot->address,
            'lat' => (float) $depot->latitude,
            'lng' => (float) $depot->longitude,
            'status' => ucfirst($depot->status),
            'total_vehicles' => 0,
            'available' => 0,
            'dispatched' => 0,
            'maintenance' => 0,
            'by_type' => [],
        ]);

        // Reset fields
        $this->name = '';
        $this->address = '';
        $this->latitude = '14.8527390';
        $this->longitude = '120.8160380';
        $this->status = 'active';
    }

    public function render()
    {
        $depots = Depot::with(['vehicles.type'])->orderBy('name')->get();

        $depotsData = $depots->map(function ($depot) {
            $vehicles = $depot->vehicles;
            $available = $vehicles->where('status', 'available')->count();
            $dispatched = $vehicles->whereIn('status', ['assigned', 'in_transit'])->count();
            $maintenance = $vehicles->where('status', 'under_maintenance')->count();
            $total = $vehicles->count();

            $byType = $vehicles->groupBy(fn ($v) => $v->type?->name ?? 'Other')
                ->map(fn ($group) => $group->count())
                ->all();

            return [
                'id' => $depot->id,
                'name' => $depot->name,
                'address' => $depot->address,
                'lat' => (float) ($depot->latitude ?? 14.8527390),
                'lng' => (float) ($depot->longitude ?? 120.8160380),
                'status' => ucfirst($depot->status),
                'total_vehicles' => $total,
                'available' => $available,
                'dispatched' => $dispatched,
                'maintenance' => $maintenance,
                'by_type' => $byType,
            ];
        });

        return view('livewire.logistics.depots', [
            'depots' => $depotsData,
            'canCreateDepot' => Rbac::allowsRoute(auth()->user()?->role, 'logistics.depots', 'create'),
            'canViewVehicles' => Rbac::allowsRoute(auth()->user()?->role, 'fleet.vehicles', 'view'),
        ]);
    }
}
