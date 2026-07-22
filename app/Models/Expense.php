<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'category',
        'supplier_id',
        'bill_number',
        'amount',
        'expense_date',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public static function categories(): array
    {
        return [
            'Direct Company Bill', 'Electricity', 'Rent', 'Salary', 'Transport',
            'Grocery', 'Maintenance', 'Marketing', 'General', 'Other',
        ];
    }

    public const DIRECT_COMPANY_BILL = 'Direct Company Bill';
}
