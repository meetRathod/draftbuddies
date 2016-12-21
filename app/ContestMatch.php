<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContestMatch extends Model
{
    protected $table = 'contest_match';
    //
    public function contest(){
        return $this->hasOne(Contest::class);
    }
    public function match(){
        return $this->hasOne(Match::class);
    }
}
 