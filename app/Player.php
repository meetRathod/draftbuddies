<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
   
    //
    public function scopeApi($query)
    {
        $columns = [
            'id',
            'uID',
            'loan',
            'name',
            'position',
            'stats',
            'last_stats'
        ];
        return $query
            ->select($columns);
    }
    public function team(){
        return $this->belongsTo(Team::class);
    }
}
