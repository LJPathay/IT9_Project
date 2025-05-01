<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The table's primary key.
     *
     * @var string
     */
    protected $primaryKey = 'transaction_id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'member_id',
        'fee_type_id',
        'item_id',
        'description',
        'amount',
        'issue_date',
        'due_date',
        'status', // pending, paid, waived, etc.
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'issue_date' => 'datetime',
        'due_date' => 'datetime',
    ];

    /**
     * Get the user (member) that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    /**
     * Get the fee type associated with the transaction.
     */
    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }

    /**
     * Get the book or item associated with the transaction.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'item_id');
    }

    /**
     * Get all payments associated with the transaction.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'transaction_id', 'transaction_id');
    }

    /**
     * Check if the transaction is overdue.
     *
     * @return bool
     */
    public function isOverdue(): bool
    {
        return $this->status === 'pending' &&
               $this->due_date &&
               $this->due_date->isPast();
    }

    /**
     * Scope a query to only include pending transactions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include paid transactions.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Format the amount as currency.
     *
     * @return string
     */
    public function getFormattedAmountAttribute(): string
    {
        return '$' . number_format($this->amount, 2);
    }
}
