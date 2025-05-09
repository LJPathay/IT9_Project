<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Loan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'copy_id',
        'member_id',
        'loan_date',
        'due_date',
        'return_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'borrowed_date' => 'datetime',
        'due_date' => 'datetime',
        'returned_date' => 'datetime',
        'renewal_date' => 'datetime',
    ];

    /**
     * Loan status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_RETURNED = 'returned';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_LOST = 'lost';
    const STATUS_DAMAGED = 'damaged';

    /**
     * Max number of renewals allowed
     */
    const MAX_RENEWALS = 2;

    /**
     * Get the user that owns the loan.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the book that is loaned.
     */
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Get the librarian who processed the loan.
     */
    public function librarian()
    {
        return $this->belongsTo(User::class, 'librarian_id');
    }

    /**
     * Get all fines associated with this loan.
     */
    public function fees()
    {
        return $this->hasMany(Fee::class);
    }

    /**
     * Get the status label for display.
     *
     * @return string
     */
    public function getStatusLabelAttribute()
    {
        if ($this->returned_date) {
            return 'Returned';
        }

        if ($this->isOverdue()) {
            $days = $this->getOverdueDays();
            return "Overdue by {$days} day" . ($days > 1 ? 's' : '');
        }

        $daysLeft = $this->getDaysUntilDue();
        if ($daysLeft === 0) {
            return 'Due today';
        }
        
        return "Due in {$daysLeft} day" . ($daysLeft > 1 ? 's' : '');
    }

    /**
     * Get the color class for status.
     *
     * @return string
     */
    public function getStatusClassAttribute()
    {
        if ($this->returned_date) {
            return 'text-success';
        }

        if ($this->isOverdue()) {
            return 'text-danger';
        }

        $daysLeft = $this->getDaysUntilDue();
        if ($daysLeft <= 2) {
            return 'text-warning';
        }
        
        return '';
    }

    /**
     * Check if the loan is overdue.
     *
     * @return bool
     */
    public function isOverdue()
    {
        return !$this->returned_date && $this->due_date < Carbon::now();
    }

    /**
     * Get the number of days this loan is overdue.
     *
     * @return int
     */
    public function getOverdueDays()
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        return Carbon::now()->diffInDays($this->due_date);
    }

    /**
     * Get the number of days until the loan is due.
     *
     * @return int
     */
    public function getDaysUntilDue()
    {
        if ($this->returned_date || $this->isOverdue()) {
            return 0;
        }

        return Carbon::now()->diffInDays($this->due_date);
    }

    /**
     * Check if the loan can be renewed.
     *
     * @return bool
     */
    public function canBeRenewed()
    {
        // Cannot renew if already returned
        if ($this->returned_date) {
            return false;
        }

        // Cannot renew if maximum renewals reached
        if ($this->renewals >= self::MAX_RENEWALS) {
            return false;
        }

        // Additional business logic can be added here
        // For example, you might not allow renewal if the book is reserved by someone else

        return true;
    }

    /**
     * Renew the loan.
     *
     * @param int $daysToExtend
     * @return bool
     */
    public function renew($daysToExtend = 14)
    {
        if (!$this->canBeRenewed()) {
            return false;
        }

        // Store current renewal date
        $this->renewal_date = Carbon::now();
        
        // Extend due date
        $newDueDate = $this->isOverdue() 
            ? Carbon::now()->addDays($daysToExtend)
            : $this->due_date->copy()->addDays($daysToExtend);
            
        $this->due_date = $newDueDate;
        $this->renewals += 1;
        $this->save();
        
        return true;
    }

    /**
     * Mark the loan as returned.
     *
     * @param array $attributes
     * @return bool
     */
    public function markAsReturned($attributes = [])
    {
        if ($this->returned_date) {
            return false;
        }

        $this->returned_date = $attributes['returned_date'] ?? Carbon::now();
        $this->status = self::STATUS_RETURNED;
        
        if (isset($attributes['notes'])) {
            $this->notes = $attributes['notes'];
        }
        
        // Update book availability
        if ($this->book) {
            $this->book->available_copies += 1;
            $this->book->save();
        }
        
        return $this->save();
    }

    /**
     * Calculate any late fees for this loan.
     *
     * @param float $feePerDay
     * @return float
     */
    public function calculateLateFee($feePerDay = 0.50)
    {
        if (!$this->isOverdue() && !($this->returned_date && $this->returned_date > $this->due_date)) {
            return 0;
        }

        $endDate = $this->returned_date ?? Carbon::now();
        $days = $this->due_date->diffInDays($endDate);
        
        return $days * $feePerDay;
    }
    
    /**
     * Create a late fee for this loan.
     *
     * @param float $feePerDay
     * @return Fee|null
     */
    public function createLateFee($feePerDay = 0.50)
    {
        $amount = $this->calculateLateFee($feePerDay);
        
        if ($amount <= 0) {
            return null;
        }
        
        // Check if a late fee already exists for this loan
        $existingFee = $this->fees()
            ->where('description', 'LIKE', 'Late return%')
            ->first();
            
        if ($existingFee) {
            // Update existing fee
            $existingFee->amount = $amount;
            $existingFee->save();
            return $existingFee;
        }
        
        // Create new fee
        return Fee::create([
            'user_id' => $this->user_id,
            'book_id' => $this->book_id,
            'loan_id' => $this->id,
            'amount' => $amount,
            'description' => 'Late return: ' . $this->book->title,
            'paid' => false,
        ]);
    }

    /**
     * Scope a query to only include active loans.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereNull('returned_date');
    }

    /**
     * Scope a query to only include overdue loans.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOverdue($query)
    {
        return $query->whereNull('returned_date')
            ->where('due_date', '<', Carbon::now());
    }

    /**
     * Scope a query to only include loans for a specific user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function bookCopy()
    {
        return $this->belongsTo(\App\Models\BookCopy::class, 'copy_id', 'copy_id');
    }

    public function member()
    {
        return $this->belongsTo(\App\Models\Member::class, 'member_id', 'member_id');
    }
}