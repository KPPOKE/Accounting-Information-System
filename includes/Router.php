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
        $path = '/' . trim($path, '/');
        $this->routes[$method][$path] = $handler;
    }

    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        $uri = urldecode($uri);
        
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $scriptDir = urldecode($scriptDir);
        
        if ($scriptDir !== '/' && strpos($uri, $scriptDir) === 0) {
            $uri = substr($uri, strlen($scriptDir));
        }

        $uri = '/' . trim($uri, '/');
        
        if ($uri === '/') {
            $uri = '/home'; 
        }

        if (isset($this->routes[$method][$uri])) {
            $handler = $this->routes[$method][$uri];
            
            if (is_string($handler) && file_exists($handler)) {
                require_once $handler;
                return;
            }
            
            if (is_callable($handler)) {
                call_user_func($handler);
                return;
            }
        }

        $errorPage = __DIR__ . '/../modules/errors/404.php';
        if (file_exists($errorPage)) {
            require_once $errorPage;
        } else {
            echo "<h1>404 Not Found</h1><p>The page you are looking for ($uri) could not be found.</p>";
        }
    }
}
