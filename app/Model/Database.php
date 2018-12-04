<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Database extends Model
{
    protected $fillable = ['title','original_file','file_path','description','created_at'];

    public function type()
    {
        return $this->belongsTo('App\Model\DataType');
    }
}
