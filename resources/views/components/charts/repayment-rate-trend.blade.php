@props(['height' => 'h-72'])

@php
    use App\Support\ChartConfig;
    use App\Support\Format;
    use App\Support\MockData;

    $data = MockData::repaymentRateTrend();

    $config = [
        'type' => 'line',
        'data' => [
            'labels' => $data['labels'],
            'datasets' => [[
                'label' => 'Repayment rate',
                'data' => $data['rate'],
                'borderColor' => Format::CHART_COLORS[0],
                'backgroundColor' => 'rgba(15,122,104,.10)',
                'fill' => true,
                'tension' => 0.35,
                'borderWidth' => 2,
                'pointRadius' => 3,
                'pointBackgroundColor' => Format::CHART_COLORS[0],
            ]],
        ],
        'options' => [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'callbacks' => ['label' => '@js:(ctx) => " Repayment rate: " + ctx.parsed.y + "%"'],
                ],
            ],
            'scales' => [
                'x' => ['grid' => ['display' => false], 'border' => ['display' => false]],
                'y' => ChartConfig::percentAxis(90, 100),
            ],
        ],
    ];
@endphp

<x-chart :config="$config" :height="$height"
         label="Line chart of monthly repayment rate over the last 12 months" />
