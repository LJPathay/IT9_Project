<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $primaryKey = 'book_id';

    protected $fillable = [
        'book_title',
        'isbn',
        'publication_date',
        'publisher',
        'category_id',
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
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function authors()
    {
        return $this->belongsToMany(Author::class, 'book_author', 'book_id', 'author_id');
    }

    public function bookCopies()
    {
        return $this->hasMany(BookCopy::class, 'book_id', 'book_id');
    }

    public function getIsAvailableAttribute()
    {
        // A book is available if it has at least one available copy
        return $this->bookCopies()->where('status', 'available')->count() > 0;
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