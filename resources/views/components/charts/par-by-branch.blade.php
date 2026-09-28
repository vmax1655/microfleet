@props(['height' => 'h-80'])

@php
    use App\Support\ChartConfig;
    use App\Support\Format;

    $branches = ['Malolos Main', 'Sta. Maria', 'SJDM', 'Baliuag', 'Plaridel'];

    // Outstanding balance per aging bucket, per branch.
    $series = [
        '1-30 days' => [2_140_000, 1_480_000, 1_920_000, 720_000, 580_000],
        '31-60 days' => [980_000, 640_000, 890_000, 310_000, 300_000],
        '61-90 days' => [520_000, 380_000, 490_000, 190_000, 160_000],
        '90+ days' => [740_000, 560_000, 880_000, 240_000, 200_000],
    ];

    $ramp = array_slice(Format::PAR_RAMP, 1); // skip "Current"

    $config = [
        'type' => 'bar',
        'data' => [
            'labels' => $branches,
            'datasets' => collect($series)->values()->map(fn ($data, $i) => [
                'label' => array_keys($series)[$i],
                'data' => $data,
                'backgroundColor' => $ramp[$i],
                'borderRadius' => 2,
                'maxBarThickness' => 46,
            ])->all(),
        ],
        'options' => [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'plugins' => [
                'legend' => ['position' => 'top', 'align' => 'end'],
                'tooltip' => ChartConfig::pesoTooltip(),
            ],
            'scales' => [
                'x' => ['stacked' => true, 'grid' => ['display' => false], 'border' => ['display' => false]],
                'y' => array_merge(ChartConfig::pesoAxis(), ['stacked' => true]),
            ],
        ],
    ];
@endphp

<x-chart :config="$config" :height="$height"
         label="Stacked bar chart of portfolio at risk by aging bucket for each branch" />
