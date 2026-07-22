<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'cashier_id', 'subtotal', 'discount', 'discount_type',
        'tax', 'service_fee', 'total', 'paid_amount', 'due_amount', 'payment_method', 'status',
        'notes', 'invoice_date',
    ];

    protected $casts = [
        'subtotal'     => 'decimal:2',
        'discount'     => 'decimal:2',
        'tax'          => 'decimal:2',
        'service_fee'  => 'decimal:2',
        'total'        => 'decimal:2',
        'paid_amount'  => 'decimal:2',
        'due_amount'   => 'decimal:2',
        'invoice_date' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function calculateProfit()
    {
        $cost = 0;
        foreach ($this->items as $item) {
            $buyPrice = $item->product->buy_price ?? 0;
            $cost += $item->qty * $buyPrice;
        }
        $discountAmt = $this->discount_type === 'percent'
            ? ($this->subtotal * $this->discount / 100)
            : $this->discount;
        
        $revenue = $this->subtotal - $discountAmt;
        return $revenue - $cost;
    }
}
