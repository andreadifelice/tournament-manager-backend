<?php

// CLIENT HTTP REQUEST -> fetch
// BACKEND -> handles request (builds and sends response)
// CLIENT HTTP handles response -> does something

/* Routes per gestione tornei */


use App\Utils\Response;
use App\Models\Tournament;
use App\Utils\Request;
use Pecee\SimpleRouter\SimpleRouter as Router;
use App\Models\TournamentTeam;

/**
 * GET /api/tournaments - Lista tornei
 */
Router::get('/tournaments', function () {
    try {
        $tournaments = Tournament::all();
        Response::success($tournaments)->send();
    } catch (\Exception $e) {
        Response::error('Errore nel recupero della lista dei tornei: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

/**
 * GET /api/tournaments/{id} - Lista tornei
 */
Router::get('/tournaments/{id}', function ($id) {
    try {
        $tournament = Tournament::find($id);

        if($tournament === null) {
            Response::error('Torneo non trovato', Response::HTTP_NOT_FOUND)->send();
        }

        Response::success($tournament)->send();
    } catch (\Exception $e) {
        Response::error('Errore nel recupero della lista dei tornei: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});


/**
 * POST /api/tournaments - Crea nuovo torneo
 */
Router::post('/tournaments', function () {
    try {
        $request = new Request();
        $data = $request->json();

        // Se la data inserita non è quella odierna da errore di validazione
        if (isset($data['date'])) {
            $today = new \DateTime();
            $today->setTime(0, 0, 0);
            $tournamentDate = \DateTime::createFromFormat('Y-m-d', $data['date']);

            if ($tournamentDate && $tournamentDate < $today) {
                Response::error('La data del torneo non può essere precedente alla data odierna.', Response::HTTP_BAD_REQUEST)->send();
                return;
            }
        }

        // Estrai gli ID delle squadre e rimuovili dai dati del torneo
        $teamIds = $data['teams'] ?? [];
        unset($data['teams']);

        // Schema per gestione partite (2, 4, 8 o 16)
        $validTeamCounts = [2, 4, 8, 16];
        if (!in_array(count($teamIds), $validTeamCounts)) {
            Response::error('Il numero di squadre deve essere esattamente 2, 4, 8 o 16', Response::HTTP_BAD_REQUEST)->send();
            return;
        }

        $errors = Tournament::validate($data);
        if (!empty($errors)) {
            Response::error('Errore di validazione', Response::HTTP_BAD_REQUEST, $errors)->send();
            return;
        }

        // Controllo se un torneo con lo stesso nome esiste già
        $existingTournaments = Tournament::where('name', $data['name']);
        if (!empty($existingTournaments)) {
            Response::error('Un torneo con questo nome esiste già', Response::HTTP_BAD_REQUEST)->send();
            return;
        }

        // Crea il torneo
        $tournament = Tournament::create([
            'name' => $data['name'],
            'date' => $data['date'],
            'location' => $data['location'],
            'status' => 'active'
        ]);

        // Se sono state fornite delle squadre, le iscrivo al torneo
        if (!empty($teamIds) && $tournament) {
            foreach ($teamIds as $teamId) {
                TournamentTeam::create([
                    'tournament_id' => $tournament->id,
                    'team_id' => (int)$teamId,
                    'status' => 'active'
                ]);
            }
            
            // Genera i match del primo round con squadre abbinate casualmente
            $tournament->generateFirstRound();
        }

        Response::success($tournament, Response::HTTP_CREATED, "Torneo creato con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante la creazione della nuovo torneo: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});


//aggiorno il torneo
Router::match(['put', 'patch'], '/tournaments/{id}', function($id) {
    try {
        $request = new Request();
        $data = $request->json();

        $tournament = Tournament::find($id);
        if($tournament === null) {
            Response::error('Torneo non trovato', Response::HTTP_NOT_FOUND)->send();
        }

        $errors = Tournament::validate(array_merge($data, ['id' => $id]));
        if (!empty($errors)) {
            Response::error('Errore di validazione', Response::HTTP_BAD_REQUEST, $errors)->send();
            return;
        }

        $tournament->update($data);

        Response::success($tournament, Response::HTTP_OK, "Torneo aggiornato con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante l\'aggiornamento del torneo: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});


//elimino il torneo
Router::delete('/tournaments/{id}', function($id) {
    try {
        $tournament = Tournament::find($id);
        if($tournament === null) {
            Response::error('Torneo non trovato', Response::HTTP_NOT_FOUND)->send();
        }

        $tournament->delete();

        Response::success(null, Response::HTTP_OK, "Torneo eliminato con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante l\'eliminazione del torneo: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});