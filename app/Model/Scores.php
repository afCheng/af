<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Scores extends Model
{
    protected $fillable = ['answer_id','score','score_content','created_at'];

    public function answer()
    {
        return $this->belongsTo('App\Model\Answers');
    }
}
