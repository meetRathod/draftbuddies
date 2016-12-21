<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Lineup extends Model
{
    //
    public function picks(){
        return $this->hasMany(LineupPick::class);
    }
    public function scopeApi($query)
    {
        $columns = [
            'id',
            'name'
        ];
        return $query
            ->select($columns);
    }
}
