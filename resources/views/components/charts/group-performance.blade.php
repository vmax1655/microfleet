@props(['height' => 'h-72'])

@php
    use App\Support\ChartConfig;
    use App\Support\Format;
    use App\Support\MockData;

    $data = MockData::groupPerformance();

    $config = [
        'type' => 'bar',
        'data' => [
            'labels' => $data['labels'],
            'datasets' => [
                [
                    'label' => 'Amount due',
                    'data' => $data['due'],
                    'backgroundColor' => Format::CHART_COLORS[3],
                    'borderRadius' => 3,
                    'maxBarThickness' => 22,
                ],
                [
                    'label' => 'Amount collected',
                    'data' => $data['collected'],
                    'backgroundColor' => Format::CHART_COLORS[0],
                    'borderRadius' => 3,
                    'maxBarThickness' => 22,
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
         label="Bar chart comparing amount due against amount collected for this center over six months" />
