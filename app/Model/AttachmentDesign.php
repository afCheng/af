<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AttachmentDesign extends Model
{
    protected $table = 'attachment_node';
    protected $fillable = ['attachment_id','node_id'];
    public $timestamps = false;
}
