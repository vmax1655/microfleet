<?php

return [
    'scikit' => [
        'enabled' => env('ML_SCIKIT_ENABLED', false),
        'url' => env('ML_SCIKIT_URL', 'http://127.0.0.1:8010'),
        'timeout' => (float) env('ML_SCIKIT_TIMEOUT', 2.0),
    ],
];
