<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostCenter extends Model
{
    protected $fillable = [
        'name',
        'description',
        'budget',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
