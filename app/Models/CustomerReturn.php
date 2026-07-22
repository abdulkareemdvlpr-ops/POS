<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id', 'cashier_id', 'customer_id',
        'items', 'total_refund', 'reason', 'status',
    ];

    protected $casts = [
        'items'        => 'array',
        'total_refund' => 'decimal:2',
    ];

    public function invoice()   { return $this->belongsTo(Invoice::class); }
    public function cashier()   { return $this->belongsTo(User::class, 'cashier_id'); }
    public function customer()  { return $this->belongsTo(Customer::class); }
}
