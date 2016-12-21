<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Symfony\Component\HttpKernel\Profiler\Profile;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'username','email', 'password',
    ];

    public function scopeApi($query)
    {
        $columns = [
            'id',
            'username',
            'name'
        ];
        return $query
            ->select($columns);
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function findForPassport($username)
    {
        if($this->where('username', $username)->count()){
            $user = $this->where('username', $username)->first();
        }else{
            $user = $this->where('email', $username)->first();
        }
        return $user;
    }

    public function profile()
    {
        return $this->hasOne(Profile::class,'user_id');
    }
    public function contests(){
        return $this->belongsToMany(Contest::class,'contest_entrants');
    }
    public function picks()
    {
        return $this->hasMany(UserPick::class,'user_id');
    }
    public function myFriends()
    {
        return $this->belongsToMany(self::class,'friends','user_id','friend_id')->withTimestamps();
    }

    public function friendsOf()
    {
        return $this->belongsToMany(self::class,'friends','friend_id','user_id')->withTimestamps();
    }
}
