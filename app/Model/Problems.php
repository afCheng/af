<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Problems extends Model
{
    protected $fillable = ['describe','answer','score','problem_time','created_at'];
    protected $hidden = ['pivot'];
    public function node()
    {
        return $this->belongsToMany('App\Model\Node','node_problem','problem_id','node_id');
    }

    public function answers()
    {
        return $this->hasMany('App\Model\Answers','problem_id');
    }
}
