<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $fillable = [
        'tender_search_id',
        'summary',
        'tender_ids',
        'status',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'tender_ids' => 'array',
            'sent_at'    => 'datetime',
        ];
    }

    public function tenderSearch(): BelongsTo
    {
        return $this->belongsTo(TenderSearch::class);
    }
}
