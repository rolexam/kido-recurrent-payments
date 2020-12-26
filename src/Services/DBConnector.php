<?php


namespace Kido\Services;

use PDO;

class DBConnector
{

    private PDO $pdo;

    public function __construct()
    {
        $dsn = "mysql:host=". Settings::getDBHost() .";dbname=" . Settings::getDBDBName() . ";charset=utf8mb4";

        $this->pdo = new PDO($dsn, Settings::getDBUsername(), Settings::getDBPassword());
    }

    public static function init() : DBConnector
    {
        return new static();
    }

    public function getPDO() : PDO
    {
        return $this->pdo;
    }

}