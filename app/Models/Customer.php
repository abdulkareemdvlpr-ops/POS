<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'phone', 'cnic',
        'city', 'address', 'notes', 'status',
    ];

    protected $casts = ['status' => 'boolean'];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments() {
        return $this->hasMany(CustomerPayment::class);
    }

    public function totalDue() {
        return $this->invoices()->sum('due_amount');
    }
}
