<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ['section_one','section_two'];
    protected $hidden = ['created_at', 'updated_at'];

    public function user()
    {
        return $this->hasMany('App\User');
    }
}
