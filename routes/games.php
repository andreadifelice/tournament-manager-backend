<?php

use App\Utils\Response;
use App\Models\Game;
use App\Models\Tournament;
use App\Models\Team;
use App\Utils\Request;
use Pecee\SimpleRouter\SimpleRouter as Router;


/**
 * GET /api/tournaments/{tournament_id}/games/ - Lista di tutte le partite
 */
Router::get('/tournaments/{tournament_id}/games', function ($tournament_id) {
    try {
        $tournament = Tournament::find($tournament_id);
        if (!$tournament) {
            Response::error('Torneo non trovato', Response::HTTP_NOT_FOUND)->send();
        }
        $games = Game::where('tournament_id', '=', $tournament_id);
        Response::success($games)->send();
    } catch (\Exception $e) {
        Response::error('Errore: ' . $e->getMessage())->send();
    }
});


/**
 * PATCH /api/tournaments/{tournament_id}/games/{game_id}
 * Aggiorna i risultati di una partita, decreta il vincitore e carica le partite del prossimo round
 */
Router::patch('/tournaments/{tournament_id}/games/{game_id}', function ($tournament_id, $game_id) {
    try {
        $tournament = Tournament::find($tournament_id);
        if (!$tournament) {
            Response::error('Torneo non trovato', Response::HTTP_NOT_FOUND)->send();
        }
        
        $game = Game::find($game_id);
        if (!$game || $game->tournament_id != $tournament_id) {
            Response::error('Partita non trovata', Response::HTTP_NOT_FOUND)->send();
        }
        
        $request = new Request();
        $data = $request->json();
        
        if (!isset($data['team_a_score']) || !isset($data['team_b_score'])) {
            Response::error('Mancano team_a_score e team_b_score', Response::HTTP_BAD_REQUEST)->send();
        }
        
        // Aggiorna i risultati e decreta il vincitore
        $game->updateResults((int)$data['team_a_score'], (int)$data['team_b_score']);
        
        // Avanza il vincitore al prossimo match
        $nextMatchId = $game->advanceWinner();
        
        // Se è stata completata l'ultima partita del round, genera il prossimo round
        $tournament->generateNextRound($game->round);
        
        // Verifica se il torneo è finito e aggiorna lo status
        $tournamentCompleted = $tournament->completeTournamentIfFinished();
        
        // Carica le partite pronte del prossimo round
        $nextRound = $game->round + 1;
        $readyGames = Tournament::getReadyGamesByRound($tournament_id, $nextRound);
        
        // Formatta i risultati con informazioni sui team
        $formattedGames = array_map(function($g) {
            return [
                'id' => $g->id,
                'round' => $g->round,
                'team_a_id' => $g->team_a_id,
                'team_a_name' => $g->team_a_id ? Team::find($g->team_a_id)->name : null,
                'team_b_id' => $g->team_b_id,
                'team_b_name' => $g->team_b_id ? Team::find($g->team_b_id)->name : null,
                'team_a_score' => $g->team_a_score,
                'team_b_score' => $g->team_b_score,
                'winner_id' => $g->winner_id,
                'next_match_id' => $g->next_match_id
            ];
        }, $readyGames);
        
        Response::success([
            'message' => 'Partita aggiornata',
            'completed_game' => [
                'id' => $game->id,
                'winner_id' => $game->winner_id,
                'next_round' => (int)$nextRound
            ],
            'tournament_completed' => $tournamentCompleted,
            'tournament_winner' => $tournamentCompleted ? [
                'winner_id' => $tournament->getTournamentWinner(),
                'winner_name' => $tournamentCompleted ? Team::find($tournament->getTournamentWinner())->name : null
            ] : null,
            'ready_games_next_round' => $formattedGames
        ])->send();
    } catch (\Exception $e) {
        Response::error('Errore: ' . $e->getMessage() . $e->getLine() . $e->getFile())->send();
    }
});
