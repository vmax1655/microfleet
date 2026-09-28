
# Generate 15 placeholder Livewire components for Fleet, Logistics, Intelligence modules

$base = "c:\xampp1\htdocs\microfleet"
Set-Location $base

# ── component definitions ────────────────────────────────────────────────────
$components = @(
    # Fleet Operations
    [PSCustomObject]@{ ns='Fleet';        class='Vehicles';     title='Vehicle Registry';         icon='truck';            label='Vehicle Registry';         desc='Manage your MFI vehicle fleet — motorcycles, vans, and service trucks.' }
    [PSCustomObject]@{ ns='Fleet';        class='Drivers';      title='Driver Management';         icon='car';              label='Driver Management';         desc='Driver profiles, licences, and performance records.' }
    [PSCustomObject]@{ ns='Fleet';        class='Reservations'; title='Reservations';              icon='clipboard-check';  label='Reservations';              desc='Book vehicles for field officer trips and center visits.' }
    [PSCustomObject]@{ ns='Fleet';        class='Dispatch';     title='Dispatch Board';            icon='navigation';       label='Dispatch Board';            desc='Real-time board for active dispatches and field positions.' }
    [PSCustomObject]@{ ns='Fleet';        class='Trips';        title='Trip Monitoring';           icon='route';            label='Trip Monitoring';           desc='Trip logs, odometer readings, and completion tracking.' }
    # Logistics & Fuel
    [PSCustomObject]@{ ns='Logistics';    class='Fuel';         title='Fuel Transactions';         icon='fuel';             label='Fuel Transactions';         desc='Fuel-up records, litre consumption, and cost per kilometer.' }
    [PSCustomObject]@{ ns='Logistics';    class='Expenses';     title='Trip Expenses';             icon='gauge';            label='Trip Expenses';             desc='Field trip allowances, tolls, parking, and miscellaneous costs.' }
    [PSCustomObject]@{ ns='Logistics';    class='Maintenance';  title='Maintenance and Repairs';   icon='wrench';           label='Maintenance and Repairs';   desc='Preventive maintenance schedules and repair work orders.' }
    [PSCustomObject]@{ ns='Logistics';    class='Routes';       title='Center Routes';             icon='compass';          label='Center Routes';             desc='Barangay center route definitions, distances, and schedules.' }
    [PSCustomObject]@{ ns='Logistics';    class='Depots';       title='Depots and Yards';          icon='truck';            label='Depots and Yards';          desc='Vehicle depot locations and overnight parking assignments.' }
    # Transport Intelligence
    [PSCustomObject]@{ ns='Intelligence'; class='Costs';        title='Transport Cost Analysis';   icon='gauge';            label='Transport Cost Analysis';   desc='Per-center and per-trip cost breakdowns linked to MFI operations.' }
    [PSCustomObject]@{ ns='Intelligence'; class='Ml';           title='ML Fuel Predictor';         icon='cpu';              label='ML Fuel Predictor';         desc='Scikit-learn Random Forest model predicting fuel consumption per trip.' }
    [PSCustomObject]@{ ns='Intelligence'; class='Variance';     title='Prediction vs Actual';      icon='sparkles';         label='Prediction vs Actual';      desc='Compare ML fuel predictions against recorded actual consumption.' }
    [PSCustomObject]@{ ns='Intelligence'; class='Efficiency';   title='Fleet Efficiency Score';    icon='gauge';            label='Fleet Efficiency Score';    desc='Composite KPI: fuel efficiency, on-time rate, and cost per km.' }
    [PSCustomObject]@{ ns='Intelligence'; class='Reports';      title='Logistics Reports';         icon='clipboard-check';  label='Logistics Reports';         desc='Downloadable PDF and CSV reports for all fleet operations.' }
)

foreach ($c in $components) {
    $nsLower  = $c.ns.ToLower()
    $clsLower = $c.class.ToLower()
    $viewKey  = "$nsLower.$clsLower"

    # ── PHP Livewire component ───────────────────────────────────────────────
    $phpDir = "$base\app\Livewire\$($c.ns)"
    if (!(Test-Path $phpDir)) { New-Item -ItemType Directory -Path $phpDir -Force | Out-Null }

    $php = "<?php`n`nnamespace App\Livewire\$($c.ns);`n`nuse Livewire\Attributes\Title;`nuse Livewire\Component;`n`n#[Title('$($c.title)')]`nclass $($c.class) extends Component`n{`n    public function render()`n    {`n        return view('livewire.$viewKey');`n    }`n}`n"
    Set-Content -Path "$phpDir\$($c.class).php" -Value $php -Encoding UTF8

    # ── Blade view ───────────────────────────────────────────────────────────
    $bladeDir = "$base\resources\views\livewire\$nsLower"
    if (!(Test-Path $bladeDir)) { New-Item -ItemType Directory -Path $bladeDir -Force | Out-Null }

    $blade = "<div>`n    <x-breadcrumb />`n`n    <x-page-header`n        title=""$($c.label)""`n        subtitle=""$($c.desc)"">`n        <x-slot:actions>`n            <x-btn icon=""download"">Export</x-btn>`n            <x-btn variant=""primary"" icon=""$($c.icon)"">New Record</x-btn>`n        </x-slot:actions>`n    </x-page-header>`n`n    <x-card class=""mt-6"">`n        <x-empty-state`n            icon=""$($c.icon)""`n            title=""$($c.label)""`n            description=""This screen is under active development. Database migrations and full UI will be available in the next implementation phase."" />`n    </x-card>`n</div>`n"
    Set-Content -Path "$bladeDir\$clsLower.blade.php" -Value $blade -Encoding UTF8

    Write-Host "  OK  $($c.ns)/$($c.class)" -ForegroundColor Green
}

Write-Host "`nAll 15 Livewire components generated." -ForegroundColor Cyan
