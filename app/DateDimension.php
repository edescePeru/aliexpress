<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DateDimension extends Model
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'date';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'date';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'date',
        'day',
        'month',
        'year',
        'day_name',
        'day_suffix',
        'day_of_week',
        'day_of_year',
        'is_weekend',
        'week',
        'week_of_month',
        'week_of_year',
        'month_name',
        'month_year',
        'month_name_year',
        'quarter',
        'quarter_name',
        'iso_year',
        'iso_week',
        'iso_day_of_week'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    protected $dates = ['date'];
}
