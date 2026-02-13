<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TransferReason extends Model
{
    protected $table = 'transfer_reasons';

    protected $fillable = ['code', 'name', 'is_active', 'sort_order'];
}
