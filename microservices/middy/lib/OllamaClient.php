<?php
use GuzzleHttp\Client;

class OllamaClient {
    private $client;
    private $url;
    private $model;
    private $timeout;

    public function __construct() {
        $this->url = rtrim(getenv('OLLAMA_URL') ?: 'http://localhost:11434/api/generate','/');
        $this->model = getenv('OLLAMA_MODEL') ?: 'llama3';
        $this->timeout = (int)(getenv('OLLAMA_TIMEOUT') ?: 30);
        $this->client = new Client(['timeout' => $this->timeout]);
    }

    public function generate(string $prompt, array $options = []) {
        $payload = array_merge([
            'model' => $this->model,
            'prompt' => $prompt,
            // puedes añadir temperature, max_length, etc según tu instalación de Ollama
        ], $options);

        $res = $this->client->post($this->url, [
            'json' => $payload,
            'headers' => ['Content-Type' => 'application/json']
        ]);
        return json_decode($res->getBody()->getContents(), true);
    }
}
