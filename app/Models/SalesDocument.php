<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesDocument extends Model
{
    protected $guarded = [];
    protected $dates = ['document_date', 'due_date'];

    public function lines()
    {
        return $this->hasMany(SalesDocumentLine::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
