<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContestInvite extends Model
{
    //
    public function contest(){
        return $this->belongsTo(Contest::class);
    }
}
