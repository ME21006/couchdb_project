<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/CouchDBConfig.php';
require_once __DIR__ . '/src/CouchDBBackup.php';

use Dotenv\Dotenv;

try {
    // 1. Cargar el .env en la raíz del proyecto
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();

    // 2. DIAGNÓSTICO: Imprimir qué variables está detectando realmente PHP
    $host     = $_ENV['COUCHDB_HOST'] ?? getenv('COUCHDB_HOST');
    $user     = $_ENV['COUCHDB_USER'] ?? getenv('COUCHDB_USER');
    $password = $_ENV['COUCHDB_PASSWORD'] ?? getenv('COUCHDB_PASSWORD');
    $database = $_ENV['COUCHDB_DATABASE'] ?? getenv('COUCHDB_DATABASE');

    // Forzar la asignación en $_ENV por si Dotenv las cargó solo en getenv()
    $_ENV['COUCHDB_HOST']     = $host;
    $_ENV['COUCHDB_USER']     = $user;
    $_ENV['COUCHDB_PASSWORD'] = $password;
    $_ENV['COUCHDB_DATABASE'] = $database;

    // Si alguna está vacía, mostramos exactamente cuál es la que está fallando
    if (!$host || !$user || !$password || !$database) {
        echo "⚠️ DIAGNÓSTICO DE VARIABLES .ENV:\n";
        echo "COUCHDB_HOST: " . ($host ?: '❌ FALTA') . "\n";
        echo "COUCHDB_USER: " . ($user ?: '❌ FALTA') . "\n";
        echo "COUCHDB_PASSWORD: " . ($password ? 'OK' : '❌ FALTA') . "\n";
        echo "COUCHDB_DATABASE: " . ($database ?: '❌ FALTA') . "\n\n";
    }

    // 3. Crear conexión reutilizando la clase CouchDBConfig
    $connection = CouchDBConfig::createConnection();

    // 4. Ejecutar respaldo
    $backupService = new CouchDBBackup($connection);
    $result = $backupService->exportDatabase();

    // 5. Visualización del resultado
    echo "========================================\n";
    echo " RESPALDO GENERADO CON ÉXITO\n";
    echo "========================================\n";
    echo " Archivo guardado en: {$result['file_path']}\n";
    echo " Total de documentos respaldados: {$result['total_docs']}\n\n";

    echo "--- DETALLE DE DOCUMENTOS RECUPERADOS ---\n";
    foreach ($result['documents'] as $index => $doc) {
        $id = $doc['_id'] ?? 'Sin ID';
        $tipo = $doc['tipo'] ?? 'Sin tipo definido';
        echo "[" . ($index + 1) . "] ID: {$id} | Tipo: {$tipo}\n";
    }

} catch (Exception $e) {
    echo " Error al ejecutar el respaldo: " . $e->getMessage() . "\n";
}