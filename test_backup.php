<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/CouchDBConfig.php';
require_once __DIR__ . '/src/CouchDBBackup.php';

use Dotenv\Dotenv;

try {
    // 1. Cargar el .env en la raíz del proyecto
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();

    // 2. Crear conexión reutilizando la clase CouchDBConfig
    $connection = CouchDBConfig::createConnection();

    // 3. Ejecutar respaldo
    $backupService = new CouchDBBackup($connection);
    $result = $backupService->exportDatabase();

    // 4. Visualización del resultado
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