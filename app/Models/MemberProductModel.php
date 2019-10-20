<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberProductModel extends Model
{
    public $table = 'MemberProducts';

    public function product(){
        return $this->belongsTo('App\Models\ProductModel', 'ProductId');
    }

}
