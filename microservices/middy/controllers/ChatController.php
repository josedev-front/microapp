<?php
namespace Middy\Controllers;

require_once __DIR__ . '/../init.php';

use Middy\Lib\OllamaClient;
use Middy\Lib\FileIndex;
use Middy\Lib\Helpers;

class ChatController {
    private $ollama;
    private $index;
    private $max_context_length = 4000;
    private $cache_ttl = 300;

    public function __construct() {
        $this->ollama = new OllamaClient();
        $this->index = new FileIndex(__DIR__ . '/../data/docs');
    }

    public function handleChat($userId, $question) {
        $question = trim($question);
        if (empty($question)) {
            return ['error' => 'Por favor, ingresa una pregunta.'];
        }
        
        if (strlen($question) > 1000) {
            return ['error' => 'La pregunta es demasiado larga. Máximo 1000 caracteres.'];
        }

        // Verificar salud del servicio Ollama
        if (!$this->ollama->healthCheck()) {
            return ['error' => 'El servicio de IA no está disponible en este momento. Por favor, intenta más tarde.'];
        }

        // Buscar contexto
        $contextParts = $this->getContextFromFiles($question);
        
        // Generar respuesta
        $response = $this->generateResponse($question, $contextParts);
        
        // Log
        $this->logConversation($userId, $question, $response);
        
        return $response;
    }

    private function getContextFromFiles($question) {
        $contextParts = [];
        $keywords = $this->extractKeywords($question);

        if (empty($keywords)) {
            return $contextParts;
        }

        // Buscar en archivos
        $files = array_merge(
            glob(__DIR__ . '/../data/docs/*.txt') ?: [],
            glob(__DIR__ . '/../data/docs/*.xlsx') ?: []
        );

        foreach ($files as $file) {
            if (count($contextParts) >= 3) break; // Límite de fragmentos
            
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $filename = basename($file);
            
            if ($extension === 'txt') {
                $content = $this->index->readTextFile($filename);
                $fragment = $this->findBestMatch($content, $keywords, $filename);
                if ($fragment) {
                    $contextParts[] = $fragment;
                }
            } elseif ($extension === 'xlsx') {
                $data = $this->index->readExcel($filename);
                $relevantData = $this->findRelevantExcelData($data, $keywords);
                if (!empty($relevantData)) {
                    $contextParts[] = "Datos de {$filename}:\n" . $relevantData;
                }
            }
        }

        return $contextParts;
    }

    // MÉTODO FALTANTE - AÑADIR ESTO
    private function extractKeywords($question) {
        $stopWords = ['que', 'como', 'donde', 'cuando', 'porque', 'para', 'con', 'de', 'la', 'el', 'los', 'las', 'un', 'una', 'unos', 'unas', 'y', 'o', 'pero', 'qué', 'cómo', 'dónde', 'cuándo', 'por qué'];
        
        $words = preg_split('/\s+/', strtolower($question));
        $keywords = array_filter($words, function($word) use ($stopWords) {
            return !in_array($word, $stopWords) && strlen($word) > 2;
        });
        
        return array_slice(array_values($keywords), 0, 5);
    }

    private function findBestMatch($content, $keywords, $filename) {
        $bestMatch = '';
        $bestScore = 0;
        
        foreach ($keywords as $keyword) {
            $pos = stripos($content, $keyword);
            if ($pos !== false) {
                $score = 1 / (($pos / strlen($content)) + 1);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $this->index->searchInText($content, $keyword, 400);
                }
            }
        }
        
        return $bestMatch ? "Del documento {$filename}: {$bestMatch}" : '';
    }

    private function findRelevantExcelData($data, $keywords) {
        $relevantRows = [];
        $maxRows = 5;
        
        foreach ($data as $rowIndex => $row) {
            if ($rowIndex >= $maxRows) break;
            
            $rowText = implode(' ', array_filter($row, function($cell) {
                return !empty($cell) && is_string($cell);
            }));
            
            foreach ($keywords as $keyword) {
                if (stripos($rowText, $keyword) !== false) {
                    $relevantRows[] = "Fila " . ($rowIndex + 1) . ": " . implode(' | ', array_slice($row, 0, 3));
                    break;
                }
            }
        }
        
        return implode("\n", array_slice($relevantRows, 0, 3));
    }

    private function generateResponse($question, $contextParts) {
        $systemPrompt = "Eres Middy, asistente especializado en gestión de servicios de Entel. 
Responde de manera profesional, concisa y útil.
Usa solo la información proporcionada en el contexto. 
Si no encuentras información, di que no tienes datos específicos.
Responde en español.";

        $context = implode("\n\n", array_slice($contextParts, 0, 3));
        
        if (empty($context)) {
            $context = "No hay información específica en los documentos disponibles.";
        }

        if (strlen($context) > $this->max_context_length) {
            $context = substr($context, 0, $this->max_context_length) . "... [información truncada]";
        }

        $prompt = "Contexto:\n{$context}\n\nPregunta: {$question}\n\nRespuesta:";

        Helpers::log("Generando respuesta", [
            'question' => substr($question, 0, 50),
            'context_fragments' => count($contextParts)
        ]);

        try {
            $ollamaResponse = $this->ollama->generate($prompt, [
                'temperature' => 0.1
            ]);

            if (isset($ollamaResponse['error'])) {
                Helpers::log("Error en Ollama", ['error' => $ollamaResponse['error']]);
                return ['error' => 'Error: ' . $ollamaResponse['error']];
            }

            $answer = $ollamaResponse['response'] ?? 'No pude generar una respuesta.';
            
            return [
                'answer' => trim($answer),
                'sources' => count($contextParts),
                'context_used' => !empty($contextParts)
            ];

        } catch (\Exception $e) {
            Helpers::log("Excepción en generateResponse", ['error' => $e->getMessage()]);
            return ['error' => 'Error interno del servicio.'];
        }
    }

    private function logConversation($userId, $question, $response) {
        $logData = [
            'user_id' => $userId,
            'question' => $question,
            'response' => isset($response['answer']) ? substr($response['answer'], 0, 100) : 'ERROR',
            'sources' => $response['sources'] ?? 0
        ];
        
        Helpers::log("Conversación", $logData);
    }
}