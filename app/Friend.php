<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Friend extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function friends()
    {
        return $this->belongsTo(User::class,'friend_id');
    }
}
