<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MemberCoinModel extends Model
{
    public $timestamps = false;

    public $table = 'MemberCoin';

    public function coin(){
        return $this->belongsTo('App\Models\CoinModel','CoinId');
    }

    public function tread(){
        return $this->hasOne('App\Models\CoinTreadModel','CoinId', 'CoinId');
    }

    public function member(){
        return $this->belongsTo('App\Models\MembersModel','MemberId');
    }
}
