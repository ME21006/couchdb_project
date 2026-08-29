<?php
interface CouchDBConnectionInterface
{
    public function getDatabase(): string;
    public function request(string $method, string $path, array $options = []): array;
}