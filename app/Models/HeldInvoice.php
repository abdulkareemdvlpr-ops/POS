<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeldInvoice extends Model
{
    protected $fillable = ['label', 'cashier_id', 'customer_id', 'data'];

    protected $casts = ['data' => 'array'];

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
