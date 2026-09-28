@props(['height' => 'h-80'])

@php
    use App\Support\ChartConfig;
    use App\Support\Format;
    use App\Support\MockData;

    $data = MockData::collectionEfficiencyTrend();

    $config = [
        'type' => 'line',
        'data' => [
            'labels' => $data['labels'],
            'datasets' => collect($data['series'])->map(fn ($s, $i) => [
                'label' => $s['label'],
                'data' => $s['data'],
                'borderColor' => Format::CHART_COLORS[$i % count(Format::CHART_COLORS)],
                'backgroundColor' => Format::CHART_COLORS[$i % count(Format::CHART_COLORS)],
                'fill' => false,
                'tension' => 0.35,
                'borderWidth' => 2,
                'pointRadius' => 2.5,
            ])->all(),
        ],
        'options' => [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'plugins' => [
                'legend' => ['position' => 'top', 'align' => 'end'],
                'tooltip' => [
                    'callbacks' => ['label' => '@js:(ctx) => " " + ctx.dataset.label + ": " + ctx.parsed.y + "%"'],
                ],
            ],
            'scales' => [
                'x' => ['grid' => ['display' => false], 'border' => ['display' => false]],
                'y' => ChartConfig::percentAxis(85, 100),
            ],
        ],
    ];
@endphp

<x-chart :config="$config" :height="$height"
         label="Line chart comparing collection efficiency across branches over 12 months" />
