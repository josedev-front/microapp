<?php
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$question_id = $_GET['question_id'] ?? '';

if (empty($question_id)) {
    echo json_encode(['success' => false, 'error' => 'Question ID requerido']);
    exit;
}

try {
    $db = getTriviaDatabaseConnection();
    $stmt = $db->prepare("SELECT * FROM question_options WHERE question_id = ? ORDER BY id");
    $stmt->execute([$question_id]);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'options' => $options
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_question_options.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
}
?>