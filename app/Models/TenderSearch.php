<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderSearch extends Model
{
    protected $fillable = [
        "user_id",
        "company",
        "sector",
        "budget_min",
        "budget_max",
        "status",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
