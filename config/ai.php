<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Evaluation Provider
    |--------------------------------------------------------------------------
    |
    | Which provider to use for AI-graded questions (open-ended, picture, etc.).
    | Supported: "gemini", "runware"
    |
    */

    'provider' => env('AI_PROVIDER', 'gemini'),

    /*
    |--------------------------------------------------------------------------
    | Runware Configuration
    |--------------------------------------------------------------------------
    |
    | Runware proxies models via its REST API. No SDK needed — uses HTTP client.
    | Model IDs use AIR format: provider:model@version
    | e.g. google:gemini@3.1-flash-lite, anthropic:claude@4.5-haiku
    |
    */

    'runware' => [
        'api_key'  => env('RUNWARE_API_KEY', ''),
        'model'    => env('RUNWARE_MODEL', 'google:gemini@3.1-flash-lite'),
        'base_url' => env('RUNWARE_BASE_URL', 'https://api.runware.ai/v1'),
        'timeout'  => env('RUNWARE_TIMEOUT', 30),
    ],

];
