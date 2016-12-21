<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Competition extends Model
{
    //
    public function scopeApi($query)
    {
        $competitions = ['EN_PR','EU_CL','ES_PL','EU_UC','IT_SA','EU_EQ'];
        return $query->select('id','sys_id','code', 'name', 'season_id','season_name')->whereIn('code',$competitions)->orWhere('code','like','%_WQ')->groupBy('sys_id')->havingRaw('max(season_id)');
    }
}
