<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Frid extends Model
{
    protected $updateTime = false;

    public function item() {
        return $this->hasOne('App\Model\Item');
    }
}
