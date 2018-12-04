<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Manifest extends Model
{
    protected $updateTime = false;

    public function apply() {
        return $this->hasMany('App\Model\Apply');
    }

    public function receive() {
        return $this->hasMany('App\Model\Receive');
    }

    public function restore() {
        return $this->hasMany('App\Model\Restore');
    }
}
