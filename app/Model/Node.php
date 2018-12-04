<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Node extends Model
{
    protected $fillable = ['name','created_at'];
    protected $hidden = ['pivot'];

    public function attachment()
    {
        return $this->belongsToMany('App\Model\Attachments','attachment_node','attachment_id','node_id');
    }

    public function problem()
    {
        return $this->hasOne('App\Model\Problems');
    }
}
