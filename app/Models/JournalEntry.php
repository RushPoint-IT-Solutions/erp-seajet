<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $guarded = [];
    protected $dates = ['journal_date', 'posted_at'];

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }
}
