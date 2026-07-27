<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Restringe as origens permitidas às UIs reais desta implantação
    | (produção: tarefas.mvndev.online / IP direto do servidor). Não usar
    | '*' — antes disso o projeto caía no default permissivo do Laravel
    | (sem config/cors.php explícito).
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://tarefas.mvndev.online',
        'http://tarefas.mvndev.online',
        'http://150.230.75.122',
        'http://150.230.75.122:25565',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
