<?php

require_once __DIR__ . '/CouchDBConnection.php';

use Dotenv\Dotenv;

class CouchDBConfig
{
    private static bool $loaded = false;

    private static function loadEnv(): void
    {
        if (self::$loaded) {
            return;
        }

        $dotenv = Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->safeLoad();
        self::$loaded = true;
    }

    public static function createConnection(): CouchDBConnection
    {
        self::loadEnv();

        $host     = $_ENV['COUCHDB_HOST'] ?? getenv('COUCHDB_HOST');
        $user     = $_ENV['COUCHDB_USER'] ?? getenv('COUCHDB_USER');
        $password = $_ENV['COUCHDB_PASSWORD'] ?? getenv('COUCHDB_PASSWORD');
        $database = $_ENV['COUCHDB_DATABASE'] ?? getenv('COUCHDB_DATABASE');

        if (!$host || !$user || !$password || !$database) {
            throw new RuntimeException(
                'Faltan variables de entorno requeridas: COUCHDB_HOST, COUCHDB_USER, COUCHDB_PASSWORD, COUCHDB_DATABASE'
            );
        }

        return new CouchDBConnection($host, $user, $password, $database);
    }
}