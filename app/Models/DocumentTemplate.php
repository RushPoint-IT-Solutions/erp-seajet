<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends Model
{
    protected $guarded = [];

    protected $casts = [
        'field_map' => 'array',
        'uses_pre_printed_form' => 'boolean',
    ];
}
