<?php

/**
 * Configuração do banco de dados.
 * Lê variáveis do ambiente (.env) com fallback para desenvolvimento local.
 */
return [
    'host'     => $_ENV['DB_HOST']     ?? getenv('DB_HOST') ?: '127.0.0.1',
    'port'     => $_ENV['DB_PORT']     ?? getenv('DB_PORT') ?: '3306',
    'database' => $_ENV['DB_NAME']     ?? getenv('DB_NAME') ?: 'gerenciador_estoque',
    'username' => $_ENV['DB_USER']     ?? getenv('DB_USER') ?: 'root',
    'password' => $_ENV['DB_PASS']     ?? getenv('DB_PASS') ?: '',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
