<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Sort extends Model
{
    protected $updateTime = false;

    public function item() {
        return $this->belongsTo('App\Model\Item');
    }

    public function items() {
        return $this->hasMany('App\Model\Item');
    }
}
