<?php

/**
 * Configurações gerais da aplicação.
 */
return [
    'name'     => $_ENV['APP_NAME']  ?? 'Brasallis ERP',
    'env'      => $_ENV['APP_ENV']   ?? 'local',
    'debug'    => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'url'      => $_ENV['APP_URL']   ?? 'http://localhost',
    'timezone' => 'America/Sao_Paulo',
    'locale'   => 'pt_BR',
];
