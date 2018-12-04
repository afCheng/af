<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Answers extends Model
{
    protected $fillable = ['problem_id','user_id','answer','created_at'];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function problem()
    {
        return $this->belongsTo('App\Model\Problems');
    }

    public function score()
    {
        return $this->hasOne('App\Model\Scores','answer_id');
    }

}
