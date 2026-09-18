<?php

namespace Alif\Permissions\Tests\Fixtures;

use Alif\Permissions\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * A user model that does not use the package trait.
 */
class PlainUser extends Authenticatable
{
    use HasUuidPrimaryKey;

    protected $table = 'users';

    protected $guarded = false;

    public $timestamps = false;
}
