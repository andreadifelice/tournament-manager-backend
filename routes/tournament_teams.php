<?php

/* Routes per la gestione delle squadre in un torneo */

use App\Models\Team;
use App\Models\Tournament;
use App\Utils\Response;
use App\Models\TournamentTeam;
use App\Utils\Request;
use Pecee\SimpleRouter\SimpleRouter as Router;

/**
 * GET /api/tournaments/{tournament_id}/teams - Lista delle squadre per un torneo
 */
Router::get('/tournaments/{tournament_id}/teams', function ($tournament_id) {
    try {
        // Verifico che il torneo esista
        $tournament = Tournament::find($tournament_id);
        if ($tournament === null) {
            Response::error("Torneo non trovato", Response::HTTP_NOT_FOUND)->send();
            return;
        }

        // Recupero le associazioni
        $tournamentTeams = TournamentTeam::where('tournament_id', '=', $tournament_id);

        $result = [];
        foreach ($tournamentTeams as $tournamentTeam) {
            $teamData = $tournamentTeam->toArray();
            $team = Team::find($tournamentTeam->team_id);
            if ($team !== null) {
                $teamData['team'] = $team->toArray();
            }
            $result[] = $teamData;
        }

        Response::success($result)->send();
    } catch (\Exception $e) {
        Response::error('Errore nel recupero della lista delle squadre del torneo: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

/**
 * POST /api/tournaments/{tournament_id}/teams - Aggiunge una squadra a un torneo
 */
Router::post('/tournaments/{tournament_id}/teams', function ($tournament_id) {
    try {
        $request = new Request();
        $data = $request->json();

        // Verifico che il torneo esista
        $tournament = Tournament::find($tournament_id);
        if ($tournament === null) {
            Response::error("Torneo non trovato", Response::HTTP_NOT_FOUND)->send();
            return;
        }

        // Aggiungo tournament_id ai dati per la validazione e creazione
        $data['tournament_id'] = (int)$tournament_id;
        $team_id = $data['team_id'];

        // Verifico che il team_id sia stato passato e esista nel db
        if (!isset($team_id)) {
            Response::error('team_id è obbligatorio', Response::HTTP_BAD_REQUEST)->send();
            return;
        }
        $team = Team::find($team_id);
        if ($team === null) {
            Response::error("Squadra non trovata", Response::HTTP_NOT_FOUND)->send();
            return;
        }

        // Verifico che la squadra non sia già iscritta al torneo
        $existingRegistration = TournamentTeam::where('tournament_id', '=', $tournament_id, 'AND', 'team_id', '=', $team_id);
        if (!empty($existingRegistration)) {
            Response::error("Squadra già iscritta a questo torneo", Response::HTTP_BAD_REQUEST)->send();
            return;
        }

        // Verifico il numero massimo di squadre e che sia uno dei valori validi
        $currentTeamsCount = count(TournamentTeam::where('tournament_id', '=', $tournament_id));
        $newTeamsCount = $currentTeamsCount + 1;

        $validTeamCounts = [2, 4, 8, 16];
        if (!in_array($newTeamsCount, $validTeamCounts)) {
            Response::error("Il numero di squadre deve essere esattamente 2, 4, 8 o 16. Attualmente hai {$currentTeamsCount} squadre.", Response::HTTP_BAD_REQUEST)->send();
            return;
        }

        $errors = TournamentTeam::validate($data);
        if (!empty($errors)) {
            Response::error('Errore di validazione', Response::HTTP_BAD_REQUEST, $errors)->send();
            return;
        }

        $tournamentTeam = TournamentTeam::create($data);

        Response::success($tournamentTeam, Response::HTTP_CREATED, "Squadra aggiunta al torneo con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante l\'iscrizione della squadra: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});
