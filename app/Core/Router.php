<?php
namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function delete(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    private function addRoute(string $method, string $path, $handler, array $middlewares): void
    {
        // Normalize path
        $path = '/' . trim($path, '/');
        if ($path === '//') $path = '/';

        $this->routes[] = [
            'method'      => $method,
            'path'        => $path,
            'handler'     => $handler,
            'middlewares' => $middlewares
        ];
    }

    public function dispatch(Request $request): void
    {
        $reqMethod = $request->getMethod();
        $reqPath   = $request->getPath();

        // Strip trailing slash except root
        if ($reqPath !== '/' && str_ends_with($reqPath, '/')) {
            $reqPath = rtrim($reqPath, '/');
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $reqMethod) {
                continue;
            }

            // Convert /user/{id} to regex
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $pattern = "#^" . $pattern . "$#";

            if (preg_match($pattern, $reqPath, $matches)) {
                // Execute middlewares
                foreach ($route['middlewares'] as $mw) {
                    if (is_callable($mw)) {
                        $mw($request);
                    }
                }

                // Filter named parameters
                $params = array_filter($matches, function ($key) {
                    return !is_numeric($key);
                }, ARRAY_FILTER_USE_KEY);

                // Call handler
                $handler = $route['handler'];
                if (is_callable($handler)) {
                    call_user_func($handler, $request, $params);
                    return;
                }

                if (is_array($handler) && count($handler) === 2) {
                    [$class, $method] = $handler;
                    $controller = new $class();
                    $controller->$method($request, $params);
                    return;
                }
            }
        }

        // Route Not Found
        if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            Response::error("API endpoint not found: {$reqMethod} {$reqPath}", 404);
        } else {
            http_response_code(404);
            $notFoundView = dirname(__DIR__, 2) . '/views/errors/404.php';
            if (file_exists($notFoundView)) {
                require $notFoundView;
            } else {
                echo "<h1>404 Not Found</h1><p>The requested page was not found.</p>";
            }
        }
    }
}
