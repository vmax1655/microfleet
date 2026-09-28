@props(['height' => 'h-72'])

@php
    use App\Support\ChartConfig;
    use App\Support\Format;
    use App\Support\MockData;

    $data = MockData::disbursementByBranch();

    $config = [
        'type' => 'bar',
        'data' => [
            'labels' => $data['labels'],
            'datasets' => [[
                'label' => 'Disbursed',
                'data' => $data['data'],
                'backgroundColor' => Format::CHART_COLORS[0],
                'borderRadius' => 4,
                'maxBarThickness' => 42,
            ]],
        ],
        'options' => [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => ['display' => false],
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
         label="Bar chart of loan disbursements by branch for the current month" />
