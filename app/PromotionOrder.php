<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class PromotionOrder extends Model
{
    use \App\Traits\RetiredLegacyPromotion;

    protected $fillable = ['table_name', 'order'];


}
