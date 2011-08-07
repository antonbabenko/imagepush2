<?php

namespace Imagepush\ImagepushBundle\Tests\Bootstrap;

class RedisConnection {
    const SERVER_VERSION   = '2.2';
    const SERVER_HOST      = '127.0.0.1';
    const SERVER_PORT      = 6379;
    const DEFAULT_DATABASE = 9;

    private static $_connection;

    public static function getConnectionArguments(array $additional = array()) {
        return array_merge(array('host' => self::SERVER_HOST, 'port' => self::SERVER_PORT), $additional);
    }

    public static function getConnectionParameters(array $additional = array()) {
        return new \Predis\ConnectionParameters(self::getConnectionArguments($additional));
    }

    public static function createConnection(array $additional = array()) {
        $serverProfile = \Predis\Profiles\ServerProfile::get(self::SERVER_VERSION);
        $connection = new \Predis\Client(self::getConnectionArguments($additional), $serverProfile);
        $connection->connect();
        $connection->select(self::DEFAULT_DATABASE);
        return $connection;
    }

    public static function getConnection($new = false) {
        if ($new == true) {
            return self::createConnection();
        }
        if (self::$_connection === null || !self::$_connection->isConnected()) {
            self::$_connection = self::createConnection();
        }
        return self::$_connection;
    }

    public static function resetConnection() {
        if (self::$_connection !== null && self::$_connection->isConnected()) {
            self::$_connection->disconnect();
            self::$_connection = self::createConnection();
        }
    }

}
?>
