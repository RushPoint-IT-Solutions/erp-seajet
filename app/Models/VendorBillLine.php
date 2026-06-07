<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorBillLine extends Model
{
    protected $guarded = [];

    public function purchaseDocument()
    {
        return $this->belongsTo(PurchaseDocument::class);
    }
}
