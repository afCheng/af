<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $updateTime = false;

    public function sort() {
        return $this->hasOne('App\Model\Sort');
    }

    public function receive() {
        return $this->hasMany('App\Model\Receive');
    }

    public function restore() {
        return $this->hasMany('App\Model\Restore');
    }

    public function frid() {
        return $this->belongsTo('App\Model\Frid');
    }

    public function sorts(){
        return $this->belongsTo('App\Model\Sort');
    }
}
