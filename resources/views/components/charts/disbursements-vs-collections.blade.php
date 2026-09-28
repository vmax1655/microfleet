@props(['height' => 'h-80'])

@php
    use App\Support\ChartConfig;
    use App\Support\Format;
    use App\Support\MockData;

    $data = MockData::disbursementsVsCollections();

    $config = [
        'type' => 'bar',
        'data' => [
            'labels' => $data['labels'],
            'datasets' => [
                [
                    'label' => 'Disbursements',
                    'data' => $data['disbursements'],
                    'backgroundColor' => Format::CHART_COLORS[0],
                    'borderRadius' => 3,
                    'maxBarThickness' => 16,
                ],
                [
                    'label' => 'Collections',
                    'data' => $data['collections'],
                    'backgroundColor' => Format::CHART_COLORS[1],
                    'borderRadius' => 3,
                    'maxBarThickness' => 16,
                ],
            ],
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
                'x' => ['grid' => ['display' => false], 'border' => ['display' => false]],
                'y' => ChartConfig::pesoAxis(),
            ],
        ],
    ];
@endphp

<x-chart :config="$config" :height="$height"
         label="Grouped bar chart comparing monthly disbursements against collections for the last 12 months" />
