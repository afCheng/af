<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Restore extends Model
{
    protected $updateTime = false;

    public function user() {
        return $this->belongsTo('App\User');
    }

    public function item() {
        return $this->belongsTo('App\Model\Item');
    }

    public function manifest() {
        return $this->belongsTo('App\Model\Manifest');
    }
}
