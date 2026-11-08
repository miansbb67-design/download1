<?php
namespace App\Core;

class Request
{
    private array $get;
    private array $post;
    private array $server;
    private array $files;
    private array $json;

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
        
        $body = file_get_contents('php://input');
        $this->json = json_decode($body, true) ?? [];
    }

    public function getMethod(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function getPath(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        
        // Strip base folder if running in subfolder like /download1
        $baseFolder = '/download1';
        if (str_starts_with($path, $baseFolder)) {
            $path = substr($path, strlen($baseFolder));
        }
        
        return '/' . ltrim($path, '/');
    }

    public function input(string $key, $default = null)
    {
        if (isset($this->json[$key])) return $this->json[$key];
        if (isset($this->post[$key])) return $this->post[$key];
        if (isset($this->get[$key])) return $this->get[$key];
        return $default;
    }

    public function all(): array
    {
        return array_merge($this->get, $this->post, $this->json);
    }

    public function file(string $key)
    {
        return $this->files[$key] ?? null;
    }

    public function getHeader(string $name): ?string
    {
        $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$headerKey] ?? null;
    }

    public function getClientIp(): string
    {
        return $this->server['HTTP_X_FORWARDED_FOR'] ?? $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}

