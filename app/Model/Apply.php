<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Apply extends Model
{
    protected $table = 'applys';

    protected $updateTime = false;

    public function manifest() {
        return $this->belongsTo('App\Model\Manifest');
    }
}
