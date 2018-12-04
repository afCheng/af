<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class GroupUser extends Model
{
    protected $table = 'group_user';
    protected $fillable = ['group_id','user_id'];
    public $timestamps = false;
}
