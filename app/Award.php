<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Award extends Model
{
    use SoftDeletes;
    //
    public function scopeApi($query)
    {
        return $query->select('id','name');
    }
    public function items(){
        return $this->hasMany(AwardItem::class);
    }

    protected $dates = ['deleted_at'];
}
