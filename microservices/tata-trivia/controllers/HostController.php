<?php
class HostController {
    private $triviaController;
    
    public function __construct() {
        $this->triviaController = new TriviaController();
    }
    
    public function setup() {
        $user = getTriviaMicroappsUser(); // Usar función actualizada
        loadTriviaView('host/setup', ['user' => $user]);
    }
    
    public function questions() {
        $user = getTriviaMicroappsUser();
        loadTriviaView('host/questions', ['user' => $user]);
    }
    
    public function lobby() {
        $user = getTriviaMicroappsUser();
        loadTriviaView('host/lobby', ['user' => $user]);
    }
    
    public function gameHost() {
        $user = getTriviaMicroappsUser();
        loadTriviaView('host/game_host', ['user' => $user]);
    }
}
?>