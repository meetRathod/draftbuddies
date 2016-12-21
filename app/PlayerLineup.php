<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PlayerLineup extends Model
{
    //
    public function match(){
        return $this->belongsTo(Match::class);
    }
}
