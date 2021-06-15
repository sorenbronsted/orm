<?php

namespace bronsted;

use Exception;
use PDO;
use stdClass;

trait DbConnection
{
    protected static stdClass $_config;
    protected static string $_dbname = 'default';

    private static $_connections = array();

    public static function setConfig(stdClass $config)
    {
        self::$_config = $config;
    }

    public static function getConnection(): PDO
    {
        if (self::$_dbname == null) {
            throw new Exception("dbname is missing");
        }

        if (isset(self::$_connections[self::$_dbname])) {
            return self::$_connections[self::$_dbname];
        }

        if (self::$_config == null) {
            throw new Exception("config is missing");
        }

        if (self::$_config->{self::$_dbname}->driver == null) {
            throw new Exception("driver for " . self::$_dbname  . "is not found");
        }

        $dsn = self::dsn(self::$_dbname);
        if (empty($dsn)) {
            throw new Exception('dsn is empty');
        }

        $pdo = new PDO($dsn, self::$_config->{self::$_dbname}->user, self::$_config->{self::$_dbname}->password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if (isset(self::$_config->{self::$_dbname}->schema)) {
            $pdo->exec('set schema ' . self::$_config->{self::$_dbname}->schema);
        }
        self::$_connections[self::$_dbname] = $pdo;
        return self::$_connections[self::$_dbname];
    }

    private static function dsn()
    {
        $result = null;
        switch (self::$_config->{self::$_dbname}->driver) {
            case 'mysql':
                $result = self::$_config->{self::$_dbname}->driver .
                    ':host='    . self::$_config->{self::$_dbname}->host .
                    ';dbname='  . self::$_config->{self::$_dbname}->name .
                    ';charset=' . self::$_config->{self::$_dbname}->charset;
                break;
            case 'sqlite':
                $result = self::$_config->{self::$_dbname}->driver .
                    ':' . self::$_config->{self::$_dbname}->name;
                break;
        }
        return $result;
    }
}
