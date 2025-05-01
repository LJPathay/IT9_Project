<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'transaction_id',
        'payment_amount',
        'payment_date',
        'payment_method', // credit_card, debit_card, bank_transfer, etc.
        'reference_number',
        'receipt_number',
        'notes',
        'processed_by', // ID of librarian or system user who processed payment
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'payment_amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    /**
     * Get the transaction associated with the payment.
     */
    public function transaction()
    {
    return $this->belongsTo(Transaction::class, 'transaction_id', 'transaction_id');
    }   

    /**
     * Get the user who processed the payment.
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Generate a unique receipt number.
     * 
     * @return string
     */
    public static function generateReceiptNumber(): string
    {
        $prefix = 'LIB-REC-';
        $timestamp = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 5));
        
        return $prefix . $timestamp . '-' . $random;
    }

    /**
     * Format the payment amount as currency.
     * 
     * @return string
     */
    public function getFormattedAmountAttribute(): string
    {
        return '$' . number_format($this->payment_amount, 2);
    }

    /**
     * Format the payment method for display.
     * 
     * @return string
     */
    public function getFormattedPaymentMethodAttribute(): string
    {
        $methods = [
            'credit_card' => 'Credit Card',
            'debit_card' => 'Debit Card',
            'bank_transfer' => 'Bank Transfer',
            'cash' => 'Cash',
        ];
        
        return $methods[$this->payment_method] ?? ucfirst(str_replace('_', ' ', $this->payment_method));
    }
}   