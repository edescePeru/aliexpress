<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShippingGuideDriver extends Model
{
    protected $table = 'shipping_guide_drivers';

    protected $fillable = [
        'shipping_guide_id',
        'is_primary',
        'document_type_code',
        'document_number',
        'first_name',
        'last_name',
        'license_number',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function guide()
    {
        return $this->belongsTo(ShippingGuide::class, 'shipping_guide_id');
    }

    public function documentType()
    {
        return $this->belongsTo(IdentityDocumentType::class, 'document_type_code', 'code');
    }
}
