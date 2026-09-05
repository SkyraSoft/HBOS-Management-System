<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Business extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'type',
        'address',
        'phone',
        'email',
        'logo',
        'currency'
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
