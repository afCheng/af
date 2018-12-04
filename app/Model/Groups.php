<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Groups extends Model
{
    protected $fillable = ['name','design_id','created_at'];

    public function design()
    {
        return $this->belongsTo('App\Model\Designs');
    }


    public function user()
    {
        return $this->belongsToMany('App\User','group_user','group_id','user_id');
    }

}
