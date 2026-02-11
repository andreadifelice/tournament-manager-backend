<?php

namespace App\Models;

use App\Traits\WithValidate;

class Tournament extends BaseModel {

    use WithValidate;

    public ?string $name = null;
    public ?string $date = null;
    public ?string $location = null;
    public ?string $status = null;

    /**
     * Nome della collection
     */
    protected static ?string $table = "tournaments";

    public function __construct(array $data = []) {
        parent::__construct($data);
    }

    protected static function validationRules(): array {
        return [
            "name" => ["required", "min:2", "max:255"],
            "date" => ["sometimes", "date_format:Y-m-d"],
            "location" => ["sometimes", "min:2", "max:255"],
            "status" => ["sometimes", "in:active,completed"],
        ];
    }

    /**
     * Genera la struttura completa del bracket per il torneo
     * Crea le partite del primo round generando gli abbinamenti casualmente
     * Le partite successive vengono create automaticamente quando i vincitori avanzano
     */
    public function generateFirstRound(): void {
        $tournamentTeams = TournamentTeam::where('tournament_id', '=', $this->id);
        
        if (empty($tournamentTeams)) {
            return;
        }

        // Estrai gli ID delle squadre
        $teamIds = array_map(fn($tt) => $tt->team_id, $tournamentTeams);
        
        // Mescola casualmente le squadre
        shuffle($teamIds);
        
        // Crea le partite del primo round
        for ($i = 0; $i < count($teamIds) - 1; $i += 2) {
            Game::create([
                'tournament_id' => $this->id,
                'round' => 1,
                'team_a_id' => $teamIds[$i],
                'team_b_id' => $teamIds[$i + 1],
                'team_a_score' => null,
                'team_b_score' => null,
                'winner_id' => null,
                'next_match_id' => null
            ]);
        }
    }

    /**
     * Carica tutte le partite di un determinato round
     */
    public static function getGamesByRound(int $tournament_id, int $round): array {
        $games = Game::where('tournament_id', '=', $tournament_id);
        $filtered = array_filter($games, fn($g) => $g->round === $round);
        return array_values($filtered); // Re-indicizza l'array
    }

    /**
     * Carica le partite pronte (con entrambi i team) di un round
     */
    public static function getReadyGamesByRound(int $tournament_id, int $round): array {
        $allGames = self::getGamesByRound($tournament_id, $round);
        return array_filter($allGames, fn($g) => $g->team_a_id !== null && $g->team_b_id !== null);
    }

    /**
     * Genera il prossimo round in base ai vincitori del round corrente
     */
    public function generateNextRound(int $currentRound): void {
        $games = self::getGamesByRound($this->id, $currentRound);
        
        // Se non ci sono giochi, non fare nulla
        if (empty($games)) {
            return;
        }
        
        // Verifica che tutti i giochi del round corrente abbiano un vincitore
        $allCompleted = array_reduce($games, fn($acc, $g) => $acc && $g->winner_id !== null, true);
        
        if (!$allCompleted) {
            return; // Non tutti i giochi sono completati, non generare il prossimo round
        }
        
        $nextRound = $currentRound + 1;
        
        // Controlla se il prossimo round esiste già
        $nextRoundGames = self::getGamesByRound($this->id, $nextRound);
        if (!empty($nextRoundGames)) {
            return; // Il prossimo round esiste già
        }
        
        // Se esiste solo 1 gioco del round corrente, è la finale e non c'è niente da generare
        if (count($games) <= 1) {
            return;
        }
        
        // Estrai i vincitori
        $winners = array_map(fn($g) => $g->winner_id, $games);
        
        // Crea le partite del prossimo round
        $previousRoundGames = [];
        for ($i = 0; $i < count($winners); $i += 2) {
            // Assicurati che ci sia un winner per entrambi i team
            if ($winners[$i] === null || !isset($winners[$i + 1]) || $winners[$i + 1] === null) {
                continue;
            }
            
            $nextGame = Game::create([
                'tournament_id' => $this->id,
                'round' => $nextRound,
                'team_a_id' => $winners[$i],
                'team_b_id' => $winners[$i + 1],
                'team_a_score' => null,
                'team_b_score' => null,
                'winner_id' => null,
                'next_match_id' => null
            ]);
            $previousRoundGames[] = $nextGame;
        }
        
        // Collega le partite del round corrente a quelle del prossimo
        $nextGameIndex = 0;
        for ($i = 0; $i < count($games); $i += 2) {
            if (isset($previousRoundGames[$nextGameIndex])) {
                // Collega la prima partita
                $games[$i]->next_match_id = $previousRoundGames[$nextGameIndex]->id;
                $games[$i]->save();
                
                // Collega la seconda partita del pair 
                if (isset($games[$i + 1])) {
                    $games[$i + 1]->next_match_id = $previousRoundGames[$nextGameIndex]->id;
                    $games[$i + 1]->save();
                }
                
                $nextGameIndex++;
            }
        }
    }

    /**
     * Restituisce il vincitore del torneo
     */
    public function getTournamentWinner(): ?int {
        $games = Game::where('tournament_id', '=', $this->id);
        
        if (empty($games)) {
            return null;
        }
        
        // Trova il round massimo
        $maxRound = max(array_map(fn($g) => $g->round, $games));
        
        // Cerca le partite nel round massimo con un vincitore
        $finalGames = array_filter($games, fn($g) => $g->round === $maxRound && $g->winner_id !== null);
        
        // Se c'è solo una partita nel round massimo con vincitore, è il vincitore del torneo
        if (count($finalGames) === 1) {
            return reset($finalGames)->winner_id;
        }
        
        return null;
    }

    /**
     * Completa il torneo se la finale è stata giocata
     * Aggiorna lo status da 'active' a 'completed'
     */
    public function completeTournamentIfFinished(): bool {
        $winner = $this->getTournamentWinner();
        
        if ($winner !== null && $this->status === 'active') {
            $this->status = 'completed';
            $this->save();
            return true;
        }
        
        return false;
    }

}