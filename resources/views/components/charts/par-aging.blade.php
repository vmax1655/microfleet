@props(['height' => 'h-24'])

@php
    use App\Support\MockData;

    $buckets = MockData::parBuckets();
    $total = array_sum(array_column($buckets, 'amount'));

    $config = [
        'type' => 'bar',
        'data' => [
            'labels' => ['Portfolio'],
            'datasets' => collect($buckets)->map(fn ($b) => [
                'label' => $b['label'],
                'data' => [$b['amount']],
                'backgroundColor' => $b['color'],
                'borderWidth' => 0,
                'barThickness' => 34,
            ])->all(),
        ],
        'options' => [
            'indexAxis' => 'y',
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'callbacks' => [
                        'label' => '@js:(ctx) => " " + ctx.dataset.label + ": " + window.LedgerFormat.peso(ctx.parsed.x)',
                    ],
                ],
            ],
            'scales' => [
                'x' => ['stacked' => true, 'display' => false, 'max' => $total],
                'y' => ['stacked' => true, 'display' => false],
            ],
            'layout' => ['padding' => 0],
        ],
    ];
@endphp

<x-chart :config="$config" :height="$height"
         label="Horizontal stacked bar showing portfolio at risk split across current, 1-30, 31-60, 61-90 and 90+ day buckets" />
