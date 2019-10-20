<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="member-token",
 *     type="object",
 *     title="用户Token"
 * )
 */
class MemberTokenModel extends Model
{
    public $table = 'MemberToken';
}
