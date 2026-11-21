<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SeasonalPromotion extends Model
{
    use \App\Traits\RetiredLegacyPromotion;

    protected $fillable = [
        'description',
        'category_id',
        'start_date',
        'end_date',
        'discount_percentage',
        'enable'
    ];

    protected $dates = ['created_at', 'updated_at', 'start_date', 'end_date'];

    public function category()
    {
        return $this->belongsTo('App\Category');
    }
}
