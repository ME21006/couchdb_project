<?php

interface CouchDBDocumentRepositoryInterface
{
    public function create(array $data, ?string $id = null): array;
    public function update(string $id, array $data): array;
    public function find(string $id): array;
    public function delete(string $id): array;
    public function findAll(bool $includeDocs = true): array
    
    // Definir aca la firma de las funciones faltantes (Find, Delete, Backup)
}