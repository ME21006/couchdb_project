<?php

require_once __DIR__ . '/CouchDBConnectionInterface.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class CouchDBConnection implements CouchDBConnectionInterface
{
    private Client $http;
    private string $database;

    public function __construct(string $host, string $user, string $password, string $database)
    {
        $this->database = $database;
        $this->http = new Client([
            'base_uri' => rtrim($host, '/') . '/',
            'auth' => [$user, $password],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'http_errors' => false
        ]);
    }

    public function getDatabase(): string
    {
        return $this->database;
    }

    public function request(string $method, string $path, array $options = []): array
    {
        try {
            $uri = $path ? "{$this->database}/" . ltrim($path, '/') : $this->database;
            $response = $this->http->request($method, $uri, $options);
            $body = (string) $response->getBody();
            
            return json_decode($body, true) ?? ['error' => 'invalid_json', 'raw' => $body];
        } catch (GuzzleException $e) {
            return ['error' => 'connection_failed', 'message' => $e->getMessage()];
        }
    }
}