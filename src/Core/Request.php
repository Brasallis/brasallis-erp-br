<?php

namespace App\Core;

/**
 * Encapsula a requisição HTTP atual.
 */
class Request
{
    private array $get;
    private array $post;
    private array $server;
    private array $files;
    private array $cookies;

    public function __construct()
    {
        $this->get     = $_GET    ?? [];
        $this->post    = $_POST   ?? [];
        $this->server  = $_SERVER ?? [];
        $this->files   = $_FILES  ?? [];
        $this->cookies = $_COOKIE ?? [];
    }

    /**
     * Retorna um valor de GET ou POST (POST tem prioridade).
     * Retorna $default se não existir.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->get[$key] ?? $default;
    }

    /** Retorna todos os dados POST. */
    public function all(): array
    {
        return array_merge($this->get, $this->post);
    }

    /** Retorna um valor específico de GET. */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->get[$key] ?? $default;
    }

    /** Retorna um valor específico de POST. */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /** Retorna informações de um arquivo enviado. */
    public function file(string $key): array|null
    {
        return $this->files[$key] ?? null;
    }

    /** Retorna o método HTTP (GET, POST, PUT, DELETE…). */
    public function method(): string
    {
        // Suporte a _method override (para formulários HTML)
        $override = $this->post['_method'] ?? $this->server['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? null;
        if ($override) {
            return strtoupper($override);
        }
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    /** Retorna a URI da requisição sem a query string. */
    public function uri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        // Remove a query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        return '/' . ltrim($uri, '/');
    }

    /** Verifica se é uma requisição AJAX. */
    public function isAjax(): bool
    {
        return ($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    /** Verifica se o método é POST. */
    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /** Retorna o IP do cliente. */
    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /** Retorna um header específico. */
    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$key] ?? null;
    }

    /** Retorna um cookie. */
    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }
}
