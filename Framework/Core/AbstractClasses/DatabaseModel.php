<?php


namespace Framework\Core\AbstractClasses;

use PDO;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Aura\SqlQuery\QueryFactory;

class DatabaseModel
{
    protected $pdo;
    protected $factory;
    protected $logger;

    function __construct()
    {
        $loggerFileName = sprintf("%sLog%sDatabase.log", dirname(dirname(dirname(__DIR__))) . DS, DS);
        $this->logger = new Logger('database');
        $this->logger->pushHandler(new RotatingFileHandler($loggerFileName, 7, Logger::DEBUG));

        $dsn = 'mysql:dbname=jokerham78;host=127.0.0.1';
        $user = 'jokerham78';
        $password = 'Lyon6900!!';

        try {
            $this->pdo = new PDO($dsn, $user, $password);
        } catch (\PDOException $exception) {
            echo 'Connecton failed: ' . $exception->getMessage();
            die();
        }
        $this->factory = new QueryFactory('mysql');
    }
}