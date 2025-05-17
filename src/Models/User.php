<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-15
 * Contact: https://t.me/alif_coder
 * Time: 6:17 PM
 */

namespace Alif\Permissions\Models;

use Alif\Permissions\Traits\HasRolesTrait;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasRolesTrait;
}