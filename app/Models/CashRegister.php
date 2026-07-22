<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'opened_at', 'closed_at', 'opening_amount',
        'cash_sales', 'customer_collections', 'supplier_payouts',
        'expenses', 'expected_closing_amount', 'actual_closing_amount',
        'difference_amount', 'status', 'notes'
    ];

    protected $casts = [
        'opened_at'               => 'datetime',
        'closed_at'               => 'datetime',
        'opening_amount'          => 'decimal:2',
        'cash_sales'              => 'decimal:2',
        'customer_collections'    => 'decimal:2',
        'supplier_payouts'        => 'decimal:2',
        'expenses'                => 'decimal:2',
        'expected_closing_amount' => 'decimal:2',
        'actual_closing_amount'   => 'decimal:2',
        'difference_amount'       => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function activeForUser($userId)
    {
        return self::active();
    }

    public static function active()
    {
        return self::where('status', 'open')->orderByDesc('opened_at')->first();
    }
}
