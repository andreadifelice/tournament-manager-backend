<?php

// CLIENT HTTP REQUEST -> fetch
// BACKEND -> handles request (builds and sends response)
// CLIENT HTTP handles response -> does something

/* Routes per gestione squadre */


use App\Utils\Response;
use App\Models\Team;
use App\Models\TournamentTeam;
use App\Utils\Request;
use Pecee\SimpleRouter\SimpleRouter as Router;

/**
 * GET /api/teams - Lista squadre
 */
Router::get('/teams', function () {
    try {
        $teams = Team::all();
        Response::success($teams)->send();
    } catch (\Exception $e) {
        Response::error('Errore nel recupero della lista delle squadre: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});

/**
 * GET /api/teams/{id} - Lista squadre
 */
Router::get('/teams/{id}', function ($id) {
    try {
        $team = Team::find($id);

        if($team === null) {
            Response::error('Squadra non trovata', Response::HTTP_NOT_FOUND)->send();
        }

        Response::success($team)->send();
    } catch (\Exception $e) {
        Response::error('Errore nel recupero della lista delle squadre: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});


/**
 * POST /api/teams - Crea nuova squadra
 */
Router::post('/teams', function () {
    try {
        $request = new Request();
        $data = $request->json();

        $errors = Team::validate($data);
        if (!empty($errors)) {
            Response::error('Errore di validazione', Response::HTTP_BAD_REQUEST, $errors)->send();
            return;
        }

        // Controllo se una squadra con lo stesso nome esiste già
        $existingTeams = Team::where('name', $data['name']);
        if (!empty($existingTeams)) {
            Response::error('Una squadra con questo nome esiste già', Response::HTTP_BAD_REQUEST)->send();
            return;
        }

        $team = Team::create($data);

        Response::success($team, Response::HTTP_CREATED, "Squadra creata con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante la creazione della nuova squadra: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});


//aggiorno la squadra
Router::match(['put', 'patch'], '/teams/{id}', function($id) {
    try {
        $request = new Request();
        $data = $request->json();

        $team = Team::find($id);
        if($team === null) {
            Response::error('Squadra non trovata', Response::HTTP_NOT_FOUND)->send();
        }

        $errors = Team::validate(array_merge($data, ['id' => $id]));
        if (!empty($errors)) {
            Response::error('Errore di validazione', Response::HTTP_BAD_REQUEST, $errors)->send();
            return;
        }

        $team->update($data);

        Response::success($team, Response::HTTP_OK, "Squadra aggiornata con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante l\'aggiornamento della squadra: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});


//elimino la squadra
Router::delete('/teams/{id}', function($id) {
    try {
        $team = Team::find($id);
        if($team === null) {
            Response::error('Squadra non trovata', Response::HTTP_NOT_FOUND)->send();
        }

        // Verifico se la squadra è iscritta o ha partecipato a qualche torneo
        $registrations = TournamentTeam::where('team_id', '=', $id);
        if (!empty($registrations)) {
            Response::error('Impossibile eliminare la squadra perché partecipa o ha partecipato a uno o più tornei.', Response::HTTP_BAD_REQUEST)->send();
            return;
        }

        $team->delete();

        Response::success(null, Response::HTTP_OK, "Squadra eliminata con successo")->send();
    } catch (\Exception $e) {
        Response::error('Errore durante l\'eliminazione della squadra: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR)->send();
    }
});