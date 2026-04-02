<?php
class Router {
    private array $routes = [];

    private function add(string $method, string $pattern, callable $handler): void {
        // Convert :param to named capture group
        $regex = preg_replace('#:([a-zA-Z_]+)#', '(?P<$1>[^/]+)', $pattern);
        $this->routes[] = [
            'method'  => strtoupper($method),
            'pattern' => "#^$regex$#",
            'handler' => $handler,
        ];
    }

    public function get(string $p, callable $h): void    { $this->add('GET',    $p, $h); }
    public function post(string $p, callable $h): void   { $this->add('POST',   $p, $h); }
    public function put(string $p, callable $h): void    { $this->add('PUT',    $p, $h); }
    public function patch(string $p, callable $h): void  { $this->add('PATCH',  $p, $h); }
    public function delete(string $p, callable $h): void { $this->add('DELETE', $p, $h); }

    public function dispatch(): void {
        $method = strtoupper($_SERVER['REQUEST_METHOD']);
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri    = rtrim($uri, '/') ?: '/';
        // Strip base path prefix (e.g. /tagihan) so routes match regardless of subdirectory
        if (defined('APP_BASE_PATH') && APP_BASE_PATH !== '' && str_starts_with($uri, APP_BASE_PATH)) {
            $uri = substr($uri, strlen(APP_BASE_PATH)) ?: '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            if (!preg_match($route['pattern'], $uri, $matches)) continue;

            // Extract named params
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            call_user_func($route['handler'], $params);
            return;
        }

        Response::error('Not found', 404);
    }
}
