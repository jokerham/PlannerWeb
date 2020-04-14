<?php
namespace Framework\Core;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Exception;

/**
 * Class Router
 * @package Framework\Core
 */
class Router
{
    protected $routes = [];
    protected $params = [];
    protected $logger;

    /**
     * Router constructor.
     */
    public function __construct()
    {
        $logFileName = sprintf("%sLog%sRouter.log", dirname(dirname(__DIR__)).DS, DS);
        $this->logger = new Logger('router');
        $this->logger->pushHandler(new RotatingFileHandler($logFileName, 7, Logger::DEBUG));
    }

    /**
     * @param $route
     * @param array $params
     */
    public function add($route, $params = [])
    {
        $route = preg_replace('/\//', '\\/', $route);
        $route = preg_replace('/\{([a-zA-Z0-9]+)\}/i', '(?P<\1>[a-zA-Z0-9-]+)', $route);
        $route = preg_replace('/\{([a-zA-Z0-9]+):([^\}]+)\}/i', '(?P<\1>\2)', $route);
        $route = '/^' . $route . '$/i';
        $this->routes[$route] = $params;
    }

    /**
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param $url
     * @return bool
     */
    public function match($url)
    {
        foreach ($this->routes as $route => $params) {
            if (preg_match($route, $url, $matches)) {
                foreach ($matches as $key => $match) {
                    if (is_string($key)) {
                        $params[$key] = $match;
                    }
                }

                $this->params = $params;
                return true;
            }
        }
        return false;
    }

    /**
     * @param $url
     * @throws Exception
     */
    public function dispatch($url)
    {
        $url = $this->removeQueryStringVariabels($url);
        $url = preg_replace('/\/$/', '', $url);

        if ($this->match($url)) {
            $controller = $this->params['module'] . 'Controller';
            $controller = $this->convertToStudlyCaps($controller);
            $controller = $this->getNamespace() . $controller;

            if (class_exists($controller)) {
                $controller_object = new $controller($this->params);
                $action = $this->params['action'];
                $action = $this->convertToCamelCase($action);

                if (preg_match('/action$/i', $action) == 0) {
                    $this->logger->info(sprintf('[%s] %s', $_SERVER['REMOTE_ADDR'], json_encode($this->params)));
                    $controller_object->$action();
                } else {
                    $errorStr = sprintf("Method %s cannot be called directly", $action);
                    $this->logger->error(sprintf('[%s] %s', $_SERVER['REMOTE_ADDR'], $errorStr));
                    throw new Exception($errorStr);
                }
            } else {
                $errorStr = sprintf("Controller class %s not found", $controller);
                $this->logger->error(sprintf('[%s] %s', $_SERVER['REMOTE_ADDR'], $errorStr));
                throw new Exception($errorStr);
            }
        } else {
            $errorStr = sprintf("No route matched : %s", $url);
            $this->logger->error(sprintf('[%s] %s', $_SERVER['REMOTE_ADDR'], $errorStr));
            throw new Exception($errorStr);
        }
    }

    /**
     * @param $url
     * @return mixed
     */
    public function removeQueryStringVariabels($url)
    {
        if ($url != '') {
            $parts = explode('?', $url);
            $url = $parts[0];
        }
        return $url;
    }

    /**
     * @param $string
     * @return mixed
     */
    private function convertToStudlyCaps($string)
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
    }

    /**
     * @param $string
     * @return string
     */
    private function convertToCamelCase($string)
    {
        return lcfirst($this->convertToStudlyCaps($string));
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        $namespace = sprintf('Framework\Module\%s\\', ucwords($this->params['module']));
        if (array_key_exists('namespace', $this->params)) {
            $namespace = $this->params['namespace'] . '\\';
        }
        return $namespace;
    }
}