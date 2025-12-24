<?php

class Router
{
    private $routes = [];

    public function get($path, $handler)
    {
        $this->add('GET', $path, $handler);
    }

    public function post($path, $handler)
    {
        $this->add('POST', $path, $handler);
    }

    private function add($method, $path, $handler)
    {
        // Normalize path: add leading slash, remove trailing slash
        $path = '/' . trim($path, '/');
        $this->routes[$method][$path] = $handler;
    }

    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        
        // CRITICAL: Parse only PATH for routing, query string is preserved in $_GET automatically
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Decode URI to handle spaces (e.g. %20 -> space)
        $uri = urldecode($uri);
        
        // Fix: Normalize slashes for Windows compatibility and decode script name
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $scriptDir = urldecode($scriptDir);
        
        if ($scriptDir !== '/' && strpos($uri, $scriptDir) === 0) {
            $uri = substr($uri, strlen($scriptDir));
        }

        // Normalize URI
        $uri = '/' . trim($uri, '/');
        
        // Default to home if empty
        if ($uri === '/') {
            $uri = '/home'; 
        }

        if (isset($this->routes[$method][$uri])) {
            $handler = $this->routes[$method][$uri];
            
            // If handler is a file path, include it
            // Note: $_GET is automatically populated by PHP from query string
            if (is_string($handler) && file_exists($handler)) {
                require_once $handler;
                return;
            }
            
            // If handler is a Closure
            if (is_callable($handler)) {
                call_user_func($handler);
                return;
            }
        }

        // 404 Not Found
        http_response_code(404);
        // Check if 404 file exists before including
        $errorPage = __DIR__ . '/../modules/errors/404.php';
        if (file_exists($errorPage)) {
            require_once $errorPage;
        } else {
            echo "<h1>404 Not Found</h1><p>The page you are looking for ($uri) could not be found.</p>";
        }
    }
}
