<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'member_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'middle_name',
        'email',
        'password',
        'contact_number',
        'join_date',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get all loans for this member.
     */
    public function loans()
    {
        return $this->hasMany(Loan::class, 'member_id');
    }

    /**
     * Get all reservations for this member.
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'member_id');
    }

    /**
     * Get all transactions for this member.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'member_id');
    }
}