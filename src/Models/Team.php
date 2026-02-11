<?php

namespace App\Models;

use App\Traits\WithValidate;
use App\Database\DB;

class Team extends BaseModel {

    use WithValidate;

    public ?string $name = null;
    public ?float $power = null;

    /**
     * Nome della collection
     */
    protected static ?string $table = "teams";

    public function __construct(array $data = []) {
        parent::__construct($data);
    }

    protected static function validationRules(): array {
        return [
            "name" => ["required", "min:2", "max:255"],
            "power" => ["sometimes", "integer", "in:2,4,8,16"],
        ];
    }

}