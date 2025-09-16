<?php 

return [
    'hh' => [
        'client_id'     => env('HH_CLIENT_ID'),
        'client_secret' => env('HH_CLIENT_SECRET'), 
        'redirect_uri'  => env('HH_REDIRECT_URI'),
        'base_url'      => env('HH_BASE_URL', 'https://hh.ru'),
        'authorize_path'=> env('HH_AUTHORIZE_PATH', '/oauth/authorize'),
        'token_path'    => env('HH_TOKEN_PATH', '/oauth/token'),
        'default_scopes'=> env('HH_DEFAULT_SCOPES', 'applicant'), 
    ],
];