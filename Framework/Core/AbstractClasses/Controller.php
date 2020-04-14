<?php
namespace Framework\Core\AbstractClasses;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

class Controller
{
    protected $route_params = [];
    protected $logger;

    public function __construct($route_params)
    {
        $this->route_params = $route_params;

        $logFileName = sprintf("%sLog%sController.log", dirname(dirname(__DIR__)) . DS, DS);
        $this->logger = new Logger('controller');
        $this->logger->pushHandler(new RotatingFileHandler($logFileName, 7, Logger::DEBUG));
    }

    public function __call($name, $args)
    {
        $exceptionName = ['__construct', '__destruct'];
        if (!in_array($name, $exceptionName)) {
            $method = $name.'Action';

            if (method_exists($this, $method)) {
                if ($this->before() !== false) {
                    call_user_func_array([$this, $method], $args);
                    $this->after();
                }
            } else {
                $errorStr = sprintf("Method %s not found in the controller %s", $method, get_class($this));
                throw new \Exception($errorStr);
            }
        }
    }

    protected function before() {
    }

    protected function after() {
    }

    /**
     * @param $configs : Array of Configuration
     * @return array
     */
    protected function getParam($configs) {
        $param = [];
        foreach ($configs as $config) {
            $key = $config[0];
            $param[$key] = $this->getParamValue($config);
        }
        return $param;
    }

    /**
     * @param $config : Configuration
     *   Configuration consists of
     *    - name of value
     *    - variable to find the param
     *    - default value of param
     * @return mixed
     */
    protected function getParamValue($config) {
        $key     = $config[0];
        $vars    = $config[1];
        $default = $config[2];
        foreach ($vars as $var) {
            if (array_key_exists($key, $var)) {
                return $var[$key];
            }
        }
        return $default;
    }
}