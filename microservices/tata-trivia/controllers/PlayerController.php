<?php
class PlayerController {
    private $triviaController;
    
    public function __construct() {
        $this->triviaController = new TriviaController();
    }
    
    public function join() {
        $user = getTriviaMicroappsUser();
        loadTriviaView('player/join', ['user' => $user]);
    }
    
    public function gamePlayer() {
        $user = getTriviaMicroappsUser();
        $player_id = $_GET['player_id'] ?? null;
        
        if (!$player_id) {
            header('Location: /microservices/tata-trivia/player/join');
            exit;
        }
        
        loadTriviaView('player/game_player', [
            'user' => $user,
            'player_id' => $player_id
        ]);
    }
}
?>