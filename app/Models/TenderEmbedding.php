<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderEmbedding extends Model
{
    protected $fillable = [
        'tender_id',
        'content',
        'embedding',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }
}
