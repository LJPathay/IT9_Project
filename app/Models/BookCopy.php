<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookCopy extends Model
{
    use HasFactory;

    protected $primaryKey = 'copy_id';
    protected $fillable = [
        'book_id',
        'acquisition_date',
        'status'
    ];

    protected $casts = [
        'acquisition_date' => 'date',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id', 'book_id');
    }

    public function loans()
    {
        return $this->hasMany(Loan::class, 'copy_id', 'copy_id');
    }
}