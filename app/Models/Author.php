<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    protected $table = 'authors';
    protected $primaryKey = 'author_id';

    protected $fillable = [
        'first_name',
        'last_name',
        'middle_name',
        'biography',
    ];

    // If you want to relate authors to books (optional, if you have a pivot table)
    // public function books()
    // {
    //     return $this->belongsToMany(Book::class, 'author_book', 'author_id', 'book_id');
    // }
}