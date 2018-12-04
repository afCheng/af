<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Characters extends Model
{
    protected $fillable = ['design_id','user_id','character'];

}
