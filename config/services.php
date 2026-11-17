<?php

return [

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash'),
        'model_cadangan' => env('GEMINI_MODEL_CADANGAN', 'gemini-3.1-flash-lite'),
        'ca_bundle' => env('GEMINI_CA_BUNDLE'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 60),
    ],

];