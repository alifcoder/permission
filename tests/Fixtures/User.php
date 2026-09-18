<?php

namespace Alif\Permissions\Tests\Fixtures;

use Alif\Permissions\Models\Concerns\HasUuidPrimaryKey;
use Alif\Permissions\Traits\HasRolesTrait;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasRolesTrait;
    use HasUuidPrimaryKey;

    protected $table = 'users';

    protected $guarded = false;

    public $timestamps = false;
}
