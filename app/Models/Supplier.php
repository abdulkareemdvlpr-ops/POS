<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'company_name', 'supplier_type', 'email', 'phone',
        'ntn', 'city', 'address', 'notes',
        'opening_balance', 'status',
    ];

    protected $casts = [
        'status'          => 'boolean',
        'opening_balance' => 'decimal:2',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function purchases() {
        return $this->hasMany(Purchase::class);
    }

    public function payments() {
        return $this->hasMany(SupplierPayment::class);
    }

    public function totalDue() {
        $opening = $this->opening_balance ?? 0;
        $purchases = $this->purchases()->sum('total_amount');
        $payments = $this->payments()->sum('amount');
        return max(0, $opening + $purchases - $payments);
    }
}
