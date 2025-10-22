<?php
class PlayerController {
    private $triviaController;
    
    public function __construct() {
        $this->triviaController = new TriviaController();
    }
    
    public function join() {
        $user = getTriviaMicroappsUser(); // Usar función actualizada
        loadTriviaView('player/join', ['user' => $user]);
    }
    
    public function gamePlayer() {
        $user = getTriviaMicroappsUser();
        loadTriviaView('player/game_player', ['user' => $user]);
    }
}
?>