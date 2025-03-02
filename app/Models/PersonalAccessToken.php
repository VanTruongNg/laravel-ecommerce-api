<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasUuids;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the ID.
     *
     * @var string
     */
    protected $keyType = 'string';
}