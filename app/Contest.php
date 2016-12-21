<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Contest extends Model
{
    //
    public function matches(){
        return $this->belongsToMany(Match::class);
    }
    public function users(){
        return $this->belongsToMany(User::class,'contest_entrants');
    }
    public function award(){
        return $this->belongsTo(Award::class);
    }
}
