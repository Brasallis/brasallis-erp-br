<?php

namespace App\Core;

/**
 * Encapsula a resposta HTTP.
 */
class Response
{
    private int $statusCode = 200;
    private array $headers  = [];

    /** Define o status HTTP. */
    public function setStatus(int $code): static
    {
        $this->statusCode = $code;
        http_response_code($code);
        return $this;
    }

    /** Adiciona um header HTTP. */
    public function setHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        header("{$name}: {$value}");
        return $this;
    }

    /**
     * Redireciona para uma URL.
     */
    public function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header("Location: {$url}");
        exit();
    }

    /**
     * Retorna JSON.
     */
    public function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    /**
     * Renderiza uma view PHP passando variáveis para ela.
     * O $viewPath deve ser RELATIVO à pasta resources/views/.
     *
     * @param string $viewPath  ex: 'auth/login'
     * @param array  $data      Variáveis para a view
     */
    public function view(string $viewPath, array $data = []): void
    {
        // Disponibiliza variáveis na view
        extract($data, EXTR_SKIP);

        $fullPath = BASE_PATH . '/resources/views/' . $viewPath . '.php';

        if (!file_exists($fullPath)) {
            $this->abort(404, "View não encontrada: {$viewPath}");
        }

        require $fullPath;
    }

    /**
     * Aborta com erro HTTP.
     */
    public function abort(int $status, string $message = ''): void
    {
        http_response_code($status);
        $errorView = BASE_PATH . '/resources/views/errors/' . $status . '.php';
        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo "<h1>Erro {$status}</h1><p>" . htmlspecialchars($message) . "</p>";
        }
        exit();
    }

    /**
     * Retorna uma resposta de texto simples.
     */
    public function text(string $content, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/plain; charset=utf-8');
        echo $content;
        exit();
    }
}
