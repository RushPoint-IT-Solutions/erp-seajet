<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseDocument extends Model
{
    protected $guarded = [];
    protected $dates = ['document_date', 'due_date'];

    public function lines()
    {
        return $this->hasMany(VendorBillLine::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
