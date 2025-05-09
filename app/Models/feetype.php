<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'rate',
        'description',
        'amount',
        'is_flat_fee', // Boolean that indicates if this is a flat fee or a per-day fee
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'is_flat_fee' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the transactions for this fee type.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Format the amount as currency.
     * 
     * @return string
     */
    public function getFormattedAmountAttribute(): string
    {
        $amount = '$' . number_format($this->amount, 2);
        
        if (!$this->is_flat_fee) {
            $amount .= ' per day';
        }
        
        return $amount;
    }
}