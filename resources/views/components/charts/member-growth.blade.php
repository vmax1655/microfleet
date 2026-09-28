@props(['height' => 'h-72'])

@php
    use App\Support\Format;
    use App\Support\MockData;

    $data = MockData::memberGrowth();

    $config = [
        'type' => 'bar',
        'data' => [
            'labels' => $data['labels'],
            'datasets' => [
                [
                    'label' => 'New members',
                    'data' => $data['new'],
                    'backgroundColor' => Format::CHART_COLORS[0],
                    'borderRadius' => 3,
                    'maxBarThickness' => 18,
                ],
                [
                    'label' => 'Exits',
                    'data' => $data['exits'],
                    'backgroundColor' => Format::CHART_COLORS[4],
                    'borderRadius' => 3,
                    'maxBarThickness' => 18,
                ],
            ],
        ],
        'options' => [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'plugins' => ['legend' => ['position' => 'top', 'align' => 'end']],
            'scales' => [
                'x' => ['grid' => ['display' => false], 'border' => ['display' => false]],
                'y' => ['beginAtZero' => true, 'grid' => ['color' => '#F0F4F2'], 'border' => ['display' => false]],
            ],
        ],
    ];
@endphp

<x-chart :config="$config" :height="$height"
         label="Bar chart of new member registrations against member exits per month" />
