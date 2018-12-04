<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'sex', 'password','section_id','status','created_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token','pivot'
    ];

    public function section()
    {
        return $this->belongsTo('App\Model\Section','section_id');
    }

    public function answers()
    {
        return $this->hasMany('App\Model\Answers');
    }

    public function design()
    {
        return $this->belongsToMany('App\Model\Designs','characters','design_id','user_id');
    }

    public function group()
    {
        return $this->belongsToMany('App\Model\Groups','group_user','group_id','user_id');
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function __construct(array $attributes = [])
    {
        $this->status = 0;
        $this->type = 1;
        parent::__construct($attributes);
    }
}
