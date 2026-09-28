@props(['height' => 'h-72'])

@php
    use App\Support\Format;
    use App\Support\MockData;

    $mix = MockData::productMix();

    $config = [
        'type' => 'doughnut',
        'data' => [
            'labels' => array_column($mix, 'label'),
            'datasets' => [[
                'data' => array_column($mix, 'value'),
                'backgroundColor' => array_slice(Format::CHART_COLORS, 0, count($mix)),
                'borderColor' => '#FFFFFF',
                'borderWidth' => 2,
                'hoverOffset' => 6,
            ]],
        ],
        'options' => [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '62%',
            'plugins' => [
                'legend' => ['position' => 'right', 'align' => 'center'],
                'tooltip' => [
                    'callbacks' => [
                        'label' => '@js:(ctx) => { const t = ctx.dataset.data.reduce((a,b)=>a+b,0); return " " + ctx.label + ": " + window.LedgerFormat.peso(ctx.parsed) + " (" + (ctx.parsed/t*100).toFixed(1) + "%)"; }',
                    ],
                ],
            ],
        ],
    ];
@endphp

<x-chart :config="$config" :height="$height"
         label="Doughnut chart of outstanding portfolio split by loan product" />
