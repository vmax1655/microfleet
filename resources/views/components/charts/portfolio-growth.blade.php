@props(['height' => 'h-80'])

@php
    use App\Support\ChartConfig;
    use App\Support\Format;
    use App\Support\MockData;

    $data = MockData::portfolioGrowth();

    $config = [
        'type' => 'line',
        'data' => [
            'labels' => $data['labels'],
            'datasets' => [
                [
                    'label' => 'Loan portfolio',
                    'data' => $data['portfolio'],
                    'borderColor' => Format::CHART_COLORS[0],
                    'backgroundColor' => 'rgba(15,122,104,.08)',
                    'fill' => true,
                    'tension' => 0.35,
                    'borderWidth' => 2,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 4,
                ],
                [
                    'label' => 'Savings balance',
                    'data' => $data['savings'],
                    'borderColor' => Format::CHART_COLORS[1],
                    'backgroundColor' => 'rgba(232,148,15,.08)',
                    'fill' => true,
                    'tension' => 0.35,
                    'borderWidth' => 2,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 4,
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
         label="Line chart of loan portfolio and savings balance growth over 12 months" />
