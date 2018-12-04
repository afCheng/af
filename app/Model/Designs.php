<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Designs extends Model
{
    protected $fillable = ['name','design_time','description','created_at'];

    public function group()
    {
        return $this->hasMany('App\Model\Groups','design_id');
    }

    public function user()
    {
        return $this->belongsToMany('App\User','characters','design_id','user_id');
    }

    public function nodes(){
        return $this->hasMany('App\Model\Node', 'design_id');
    }
}
