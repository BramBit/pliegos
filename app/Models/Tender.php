<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tender extends Model
{
    protected $fillable = [
        'process_id',
        'title',
        'description',
        'entity',
        'city',
        'department',
        'budget',
        'contract_type',
        'status',
        'published_at',
        'url',
        'sector',
        'indexed',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'indexed'      => 'boolean',
        ];
    }
}
