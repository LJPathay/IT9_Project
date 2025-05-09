<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'category_id';

    protected $fillable = [
        'name',
        'description',
    ];

    // If you want to define the relationship to books:
    public function books()
    {
        return $this->hasMany(Book::class, 'category_id', 'category_id');
    }
}