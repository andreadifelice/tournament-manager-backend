<?php

namespace App\Models;

use App\Traits\WithValidate;

class TournamentTeam extends BaseModel {

    use WithValidate;

    public ?int $tournament_id = null;
    public ?int $team_id = null;

    /**
     * Nome della collection
     */
    protected static ?string $table = "tournament_teams";

    public function __construct(array $data = []) {
        parent::__construct($data);
    }

    protected static function validationRules(): array {
        return [
            "tournament_id" => ["required", "integer"],
            "team_id" => ["required", "integer"],
        ];
    }

    /**
     * Relazione con Tournament
     */
    public function tournament() {
        return Tournament::find($this->tournament_id);
    }

    /**
     * Relazione con Team
     */
    public function team() {
        return Team::find($this->team_id);
    }

}