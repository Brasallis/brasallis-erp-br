<?php

namespace App\Core;

/**
 * Security — utilitário de criptografia para dados sensíveis.
 */
class Security
{
    private static ?string $key = null;

    /**
     * Inicializa a chave de criptografia a partir do .env.
     */
    private static function init(): void
    {
        if (self::$key === null) {
            self::$key = $_ENV['APP_KEY'] ?? 'default_fallback_key_change_me';
        }
    }

    /**
     * Criptografa um valor.
     */
    public static function encrypt(string $value): string
    {
        self::init();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($value, 'aes-256-cbc', self::$key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    /**
     * Descriptografa um valor.
     */
    public static function decrypt(string $value): ?string
    {
        self::init();
        try {
            $parts = explode('::', base64_decode($value), 2);
            if (count($parts) !== 2) return null;
            
            [$encrypted, $iv] = $parts;
            return openssl_decrypt($encrypted, 'aes-256-cbc', self::$key, 0, $iv);
        } catch (\Exception $e) {
            return null;
        }
    }
}
