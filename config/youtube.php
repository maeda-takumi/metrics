<?php

return [
    'api_key' => getenv('YOUTUBE_API_KEY') ?: '',
    'base_url' => 'https://www.googleapis.com/youtube/v3',
    'max_batch_size' => 45, // Safer than the API limit of 50 to avoid URL length issues
];