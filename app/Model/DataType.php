<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DataType extends Model
{
    protected $fillable = ['name'];
    public $timestamps = false;

    public function database()
    {
        return $this->hasMany('App\Model\Database','type_id');
    }
}
