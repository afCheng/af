<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Attachments extends Model
{
    protected $fillable = ['original_file','file_path','description','created_at'];
    protected $hidden = ['pivot'];

    public function node()
    {
        return $this->belongsToMany('App\Model\Node','attachment_node','attachment_id','node_id');
    }
}
