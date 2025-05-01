<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Member extends Authenticatable
{
    use HasFactory;

    protected $primaryKey = 'member_id'; 

    protected $fillable = [
        'first_name',
        'last_name',
        'middle_name',
        'email',
        'password',
        'contact_number',
        'join_date'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}