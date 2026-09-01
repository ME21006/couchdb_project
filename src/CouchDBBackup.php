<?php

class CouchDBBackup
{
    private CouchDBConnectionInterface $connection;

    public function __construct(CouchDBConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Genera un archivo JSON con los documentos respaldados de la base de datos.
     */
    public function exportDatabase(?string $outputDir = null): array
    {
        // Petición a CouchDB para obtener todos los documentos
        $response = $this->connection->request('GET', '_all_docs', [
            'query' => ['include_docs' => 'true']
        ]);

        if (isset($response['error'])) {
            throw new RuntimeException("Error al obtener documentos: " . ($response['reason'] ?? $response['error']));
        }

        $rows = $response['rows'] ?? [];
        $documents = array_map(fn($row) => $row['doc'], $rows);

        // Directorio de almacenamiento
        $dir = $outputDir ?? dirname(__DIR__) . '/backups';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $dbName = $this->connection->getDatabase();
        $filePath = "{$dir}/backup_{$dbName}_" . date('Y-m-d_H-i-s') . ".json";

        $jsonContent = json_encode($documents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (file_put_contents($filePath, $jsonContent) === false) {
            throw new RuntimeException("No se pudo escribir el archivo en: {$filePath}");
        }

        return [
            'file_path' => $filePath,
            'total_docs' => count($documents),
            'documents' => $documents
        ];
    }
}