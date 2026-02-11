<?php

namespace App\Models;

use App\Traits\WithValidate;

class Game extends BaseModel {

    use WithValidate;

    public ?int $tournament_id = null;
    public ?int $round = null;
    public ?int $team_a_id = null;
    public ?int $team_b_id = null;
    public ?int $team_a_score = null;
    public ?int $team_b_score = null;
    public ?int $winner_id = null;
    public ?int $next_match_id = null;

    /**
     * Nome della collection
     */
    protected static ?string $table = "games";

    public function __construct(array $data = []) {
        parent::__construct($data);
    }

    protected static function validationRules(): array {
        return [
            "tournament_id" => ["required", "integer"],
            "round" => ["required", "integer"],
            "team_a_id" => ["required", "integer"],
            "team_b_id" => ["required", "integer"],
            "team_a_score" => ["sometimes", "integer", "min:0"],
            "team_b_score" => ["sometimes", "integer", "min:0"],
            "winner_id" => ["sometimes", "integer"],
            "next_match_id" => ["sometimes", "integer"],
        ];
    }

    /**
     * Aggiorna i risultati della partita e decreta il vincitore
     */
    public function updateResults(int $team_a_score, int $team_b_score): void {
        $this->team_a_score = $team_a_score;
        $this->team_b_score = $team_b_score;
        
        // Determina il vincitore in base ai punteggi
        if ($team_a_score > $team_b_score) {
            $this->winner_id = $this->team_a_id;
        } else if ($team_b_score > $team_a_score) {
            $this->winner_id = $this->team_b_id;
        } else {
            // In caso di pareggio è null
            throw new \Exception('Pareggio non consentito');
        }
        
        $this->save();
    }

    /**
     * Avanza il vincitore alla partita successiva
     */
    public function advanceWinner(): ?int {
        if (!$this->winner_id || !$this->next_match_id) {
            return null;
        }
        
        $nextGame = Game::find($this->next_match_id);
        if (!$nextGame) {
            return null;
        }
        
        // Se team_a_id è vuoto, assegna qui il vincitore
        if ($nextGame->team_a_id === null) {
            $nextGame->team_a_id = $this->winner_id;
        } 
        // Altrimenti assegna a team_b_id
        else if ($nextGame->team_b_id === null) {
            $nextGame->team_b_id = $this->winner_id;
        }
        
        $nextGame->save();
        
        return $this->next_match_id;
    }
}