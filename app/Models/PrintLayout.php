<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintLayout extends Model
{
    protected $guarded = [];

    protected $casts = [
        'print_template' => 'boolean',
        'positions' => 'array',
        'field_styles' => 'array',
        'custom_fields' => 'array',
        'is_default' => 'boolean',
    ];
}
