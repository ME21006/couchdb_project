<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/CouchDBConnection.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// 1. Instanciar la Conexión
$connection = new CouchDBConnection(
    $_ENV['COUCHDB_HOST'],
    $_ENV['COUCHDB_USER'],
    $_ENV['COUCHDB_PASSWORD'],
    $_ENV['COUCHDB_DB']
);
