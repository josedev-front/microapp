<?php
namespace Middy\Lib;

require_once __DIR__ . '/../init.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class OllamaClient {
    private $client;
    private $base_url;
    private $model;
    private $timeout;

    public function __construct() {
        $this->base_url = $_ENV['OLLAMA_URL'] ?? 'http://localhost:11434';
        $this->model = $_ENV['OLLAMA_MODEL'] ?? 'llama3.2:3b';
        $this->timeout = (int)($_ENV['OLLAMA_TIMEOUT'] ?? 60);
        
        $this->client = new Client([
            'timeout' => $this->timeout,
            'connect_timeout' => 10,
            'read_timeout' => $this->timeout,
        ]);
    }

    public function generate(string $prompt, array $options = []) {
        $url = rtrim($this->base_url, '/') . '/api/generate';

        Helpers::log("🔵 Enviando prompt a Ollama", [
            'model' => $this->model,
            'prompt_length' => strlen($prompt),
            'url' => $url
        ]);

        $payload = array_merge([
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'temperature' => 0.1,
                'num_predict' => 500,
                'top_k' => 40,
                'top_p' => 0.9,
            ]
        ], $options);

        try {
            $response = $this->client->post($url, [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'timeout' => $this->timeout
            ]);
            
            $content = $response->getBody()->getContents();
            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON inválido en respuesta');
            }
            
            if (!isset($data['response'])) {
                Helpers::log("🟡 Respuesta incompleta", ['data' => $data]);
                return ['error' => 'El servicio no devolvió una respuesta válida'];
            }
            
            Helpers::log("🟢 Respuesta exitosa", [
                'response_length' => strlen($data['response']),
                'model' => $data['model'] ?? 'unknown'
            ]);
            
            return $data;
            
        } catch (RequestException $e) {
            $errorMsg = $e->getMessage();
            Helpers::log("🔴 Error de request", [
                'error' => $errorMsg,
                'prompt' => substr($prompt, 0, 100)
            ]);
            
            return ['error' => 'Error de conexión: ' . $errorMsg];
            
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            Helpers::log("🔴 Error general", ['error' => $errorMsg]);
            return ['error' => 'Error: ' . $errorMsg];
        }
    }

    public function healthCheck(): bool {
        try {
            // URL CORREGIDA: quitar /api/generate del base_url
            $base = rtrim($this->base_url, '/');
            $base = str_replace('/api/generate', '', $base); // Limpiar si viene con /api/generate
            $url = $base . '/api/tags';
            
            $response = $this->client->get($url, ['timeout' => 5]);
            $data = json_decode($response->getBody()->getContents(), true);
            
            $isHealthy = isset($data['models']) && is_array($data['models']);
            Helpers::log("🟢 Health check", [
                'status' => $isHealthy ? 'healthy' : 'unhealthy',
                'models_count' => count($data['models'] ?? [])
            ]);
            
            return $isHealthy;
            
        } catch (\Exception $e) {
            Helpers::log("🔴 Health check failed", ['error' => $e->getMessage()]);
            return false;
        }
    }
}