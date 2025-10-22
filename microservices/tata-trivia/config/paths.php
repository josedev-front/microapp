<?php
// Configuración de paths para Tata Trivia - Sin conflictos con Microapps

// Solo definir si no existen
if (!defined('TATA_BASE_URL')) {
    define('TATA_BASE_URL', '/microservices/tata-trivia');
    define('TATA_ASSETS_URL', TATA_BASE_URL . '/assets');
}

// URLs para vistas
define('TATA_URL_WELCOME', TATA_BASE_URL . '/');
define('TATA_URL_HOST_SETUP', TATA_BASE_URL . '/host/setup');
define('TATA_URL_HOST_QUESTIONS', TATA_BASE_URL . '/host/questions');
define('TATA_URL_HOST_LOBBY', TATA_BASE_URL . '/host/lobby');
define('TATA_URL_HOST_GAME', TATA_BASE_URL . '/host/game');
define('TATA_URL_HOST_HISTORY', TATA_BASE_URL . '/host/history');
define('TATA_URL_PLAYER_JOIN', TATA_BASE_URL . '/player/join');
define('TATA_URL_PLAYER_GAME', TATA_BASE_URL . '/player/game');
define('TATA_URL_PLAYER_HISTORY', TATA_BASE_URL . '/player/history');
define('TATA_URL_RESULTS', TATA_BASE_URL . '/results');

// URLs para APIs
define('TATA_API_CREATE_TRIVIA', TATA_BASE_URL . '/api/create_trivia.php');
define('TATA_API_JOIN_GAME', TATA_BASE_URL . '/api/join_game.php');
define('TATA_API_GAME_DATA', TATA_BASE_URL . '/api/game_data.php');
define('TATA_API_LOBBY_PLAYERS', TATA_BASE_URL . '/api/get_lobby_players.php');
define('TATA_API_SUBMIT_ANSWER', TATA_BASE_URL . '/api/submit_answer.php');
define('TATA_API_GAME_ACTIONS', TATA_BASE_URL . '/api/game_actions.php');
define('TATA_API_GET_RESULTS', TATA_BASE_URL . '/api/get_results.php');
?>