<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author',
        'isbn',
        'publication_year',
        'publisher',
        'category_id',
        'description',
        'total_copies',
        'available_copies',
        'cover_image',
    ];

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function fees()
    {
        return $this->hasMany(Fee::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}

class Fee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'loan_id',
        'amount',
        'description',
        'paid',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}