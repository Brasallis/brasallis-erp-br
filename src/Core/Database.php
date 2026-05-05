<?php

namespace App\Core;

use PDO;
use PDOException;
use Exception;

/**
 * Singleton para a conexão PDO com o banco de dados.
 * Lê configurações do array retornado por config/database.php.
 */
class Database
{
    private static ?PDO $instance = null;

    /** Impede instanciação direta. */
    private function __construct() {}

    /**
     * Retorna a instância PDO singleton.
     *
     * @throws Exception em caso de erro de conexão
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            if (!defined('BASE_PATH')) {
                define('BASE_PATH', dirname(__DIR__, 2));
            }
            $config = require \BASE_PATH . '/config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $config['host'],
                $config['port'],
                $config['database']
            );

            try {
                self::$instance = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            } catch (PDOException $e) {
                throw new Exception('Erro ao conectar ao banco de dados: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }

    /** Para testes: permite reiniciar a conexão. */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
