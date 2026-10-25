<?php

return [
    'book_index' => resource_path('knowledge/worlds-fittest-book.json'),
    'additional_book_indexes' => [resource_path('knowledge/program-latihan-12-minggu.json')],
    'provider' => env('WORKOUT_CHAT_PROVIDER', 'openai'),
    'api_key' => env('OPENAI_API_KEY'),
    'model' => env('WORKOUT_CHAT_MODEL', 'gpt-4.1-mini'),
    'gemini_api_key' => env('GEMINI_API_KEY'),
    'gemini_model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
    'timeout' => 25,
];
