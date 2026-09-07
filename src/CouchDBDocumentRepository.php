<?php

require_once __DIR__ . '/CouchDBConnectionInterface.php';
require_once __DIR__ . '/CouchDBDocumentRepositoryInterface.php';

// Clase que maneja las funciones CRUD para una base de datos CouchDB
// TODO: Desarrollar las funciones restantes (Find, Delete, Backup)

class CouchDBDocumentRepository implements CouchDBDocumentRepositoryInterface
{
    private CouchDBConnectionInterface $connection;

    public function __construct(CouchDBConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Funcion que crea un documento nuevo.
     * Si se proporciona un id, se usa PUT para guardar con id generado por el usuario.
     * Si no, se usa POST y se deja que Couch cree un id automaticamente para el documento.
     */
    public function create(array $data, ?string $id = null): array
    {
        if ($id !== null) {
            $result = $this->connection->request('PUT', $id, [
                'json' => $data
            ]);
        } else {
            $result = $this->connection->request('POST', '', [
                'json' => $data
            ]);
        }

        if (isset($result['error']) && $result['error'] !== 'invalid_json') {
            throw new RuntimeException(
                "Error al crear documento: {$result['error']} - " . ($result['reason'] ?? $result['message'] ?? '')
            );
        }

        return $result;
    }

    /**
     * Funcion que actualiza un documento existente.
     * Recupera automáticamente el campo de revision (_rev) que posee actualmente 
     * antes de aplicar los cambios.
     */
    public function update(string $id, array $data): array
    {
        $current = $this->find($id);

        if (isset($current['error'])) {
            throw new RuntimeException(
                "No se pudo obtener el documento '{$id}' para actualizar: " . ($current['reason'] ?? $current['error'])
            );
        }

        $updatedData = array_merge($current, $data);
        $updatedData['_id']  = $id;
        $updatedData['_rev'] = $current['_rev'];

        $result = $this->connection->request('PUT', $id, [
            'json' => $updatedData
        ]);

        if (isset($result['error'])) {
            throw new RuntimeException(
                "Error al actualizar documento '{$id}': {$result['error']} - " . ($result['reason'] ?? '')
            );
        }

        return $result;
    }

    public function find(string $id): array
    {
        return $this->connection->request('GET', $id);
    }


    //Elimina un documento de CouchDB consultando su revisión (_rev) actual para evitar conflictos de concurrencia.
     
    public function delete(string $id): array
    {
        $current = $this->find($id);

        if (isset($current['error'])) {
            throw new RuntimeException(
                "No se pudo obtener el documento '{$id}' para eliminar: " . ($current['reason'] ?? $current['error'])
            );
        }

        $result = $this->connection->request('DELETE', $id . '?rev=' . urlencode($current['_rev']));

        if (isset($result['error'])) {
            throw new RuntimeException(
                "Error al eliminar el documento '{$id}': {$result['error']} - " . ($result['reason'] ?? '')
            );
        }

        return $result;
    }
    
    //Recupera todos los documentos existentes en la base de datos (_all_docs).
    
    public function findAll(bool $includeDocs = true): array
    {
        $endpoint = '_all_docs' . ($includeDocs ? '?include_docs=true' : '');
        $result = $this->connection->request('GET', $endpoint);

        if (isset($result['error'])) {
            throw new RuntimeException(
                "Error al consultar todos los documentos: {$result['error']} - " . ($result['reason'] ?? '')
            );
        }

        return $result;
    }

}