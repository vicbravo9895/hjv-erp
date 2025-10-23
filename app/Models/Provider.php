<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    protected $fillable = [
        'name',
        'contact_name',
        'phone',
        'email',
        'address',
        'service_type',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
