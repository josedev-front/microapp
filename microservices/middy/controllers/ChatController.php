<?php
require_once __DIR__ . '/../lib/OllamaClient.php';
require_once __DIR__ . '/../lib/FileIndex.php';

class ChatController {
    private $ollama;
    private $index;

    public function __construct() {
        $this->ollama = new OllamaClient();
        $this->index = new FileIndex(__DIR__ . '/../data/docs');
    }

    public function handleChat($userId, $question) {
        // 1. Validaciones básicas
        $question = trim($question);
        if (!$question) return ['error' => 'Pregunta vacía'];

        // 2. Buscar en ficheros: ejemplo simple, busca por palabras clave en cada txt/xlsx
        $contextParts = [];

        // Leer todos los TXT
        foreach (glob(__DIR__ . '/../data/docs/*.txt') as $txt) {
            $text = $this->index->readTextFile(basename($txt));
            // si la pregunta contiene una palabra clave, adjuntamos fragmento
            $frag = $this->index->searchInText($text, strtok($question, ' '), 800);
            if ($frag) $contextParts[] = "Desde {$txt}: " . $frag;
        }

        // Leer excels y convertir algunas filas a texto
        foreach (glob(__DIR__ . '/../data/docs/*.xlsx') as $xls) {
            $rows = $this->index->readExcel(basename($xls));
            // ejemplo: juntar primeras N filas como resumen
            $summary = [];
            $max = min(20, count($rows));
            for ($i=0;$i<$max;$i++) $summary[] = implode(' | ', $rows[$i]);
            if (!empty($summary)) $contextParts[] = "Resumen de " . basename($xls) . ": " . implode("\n", $summary);
        }

        // 3. Construir prompt: sistema + contexto + pregunta (acotar longitud)
        $system = "Eres Middy, asistente interno profesional. Responde de forma corta y precisa al ejecutivo. Si la información solicitada no está en el contexto di que no se encuentra y sugiere cómo obtenerla.";
        $context = implode("\n\n---\n\n", $contextParts);
        // Trunca contexto si es muy largo
        if (strlen($context) > 4000) $context = substr($context, 0, 4000) . "\n\n[...]";

        $prompt = $system . "\n\nContexto disponible:\n" . $context . "\n\nPregunta: " . $question . "\n\nRespuesta:";
        // 4. Llamada a Ollama
        $res = $this->ollama->generate($prompt, ['temperature' => 0.1]);

        // 5. Normalizar respuesta
        if (isset($res['error'])) return ['error' => $res['error']];
        // Ajusta según el formato de salida de tu Ollama (a veces viene en 'choices' o 'response')
        $generated = $res['response'] ?? ($res['choices'][0]['text'] ?? null);
        return ['answer' => $generated];
    }
}
