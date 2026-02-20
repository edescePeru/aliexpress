<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IdentityDocumentType extends Model
{
    protected $table = 'identity_document_types';

    protected $fillable = ['code', 'name', 'is_active', 'sort_order'];
}
