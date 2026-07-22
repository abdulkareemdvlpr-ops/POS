<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id', 'product_id', 'qty', 'price', 'total', 'unit_type', 'unit_qty',
    ];

    protected $casts = [
        'price'    => 'decimal:2',
        'total'    => 'decimal:2',
        'qty'      => 'integer',
        'unit_qty' => 'integer',
    ];

    public function unitLabel(): string
    {
        return match ($this->unit_type) {
            'box'   => 'Box',
            'strip' => 'Strip',
            default => 'Tablet',
        };
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
