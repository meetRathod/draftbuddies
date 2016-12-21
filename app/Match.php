<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Match extends Model
{
    //
    public function team1(){
        return $this->hasOne(Team::class,'uID','team_1');
    }
    public function team2(){
        return $this->hasOne(Team::class,'uID','team_2');
    }
    public function contests(){
        return $this->belongsToMany(Contest::class,'contest_match');
    }

    public function scopeApi($query)
    {
        $columns = [
            'id',
            'group_name',
            'match_day',
            'match_type',
            'round_number',
            'round_type',
            'venue',
            'city',
            'scheduled_on',
            'team_1',
            'team_2',
            'entry_type'
        ];
        return $query
            ->select($columns);
    }
}
