<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'generic_name', 'category_id', 'product_type', 'supplier_id', 'sku', 'barcode',
        'batch_number', 'mfg_date', 'expiry_date',
        'buy_price', 'price', 'stock', 'low_stock_threshold',
        'unit', 'strips_per_box', 'tablets_per_strip', 'units_per_box', 'volume', 'description', 'image', 'status',
        'almari', 'khana', 'row',
    ];

    protected $casts = [
        'status'              => 'boolean',
        'buy_price'           => 'decimal:2',
        'price'               => 'decimal:2',
        'stock'               => 'integer',
        'low_stock_threshold' => 'integer',
        'strips_per_box'      => 'integer',
        'tablets_per_strip'   => 'integer',
        'units_per_box'       => 'integer',
        'mfg_date'            => 'date',
        'expiry_date'         => 'date',
    ];

    public const TYPE_MEDICINE = 'medicine';
    public const TYPE_GENERAL  = 'general';
    public const TYPE_LIQUID   = 'liquid';

    // ── Relationships ──────────────────────────────────────────────
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    // ── Packaging / Dynamic Price Helpers ─────────────────────────
    // buy_price / price always store the SINGLE UNIT (loose tablet/piece) price.
    // Strip and box prices are derived, never stored, so admins only ever enter one number.

    public function unitMultiplier(string $unitType): int
    {
        $tabletsPerStrip = max(1, (int) $this->tablets_per_strip);
        $stripsPerBox    = max(1, (int) $this->strips_per_box);

        return match ($unitType) {
            'box'   => $this->product_type === self::TYPE_LIQUID ? max(1, (int) $this->units_per_box) : $tabletsPerStrip * $stripsPerBox,
            'strip' => $tabletsPerStrip,
            default => 1,
        };
    }

    public function unitsPerBox(): int
    {
        if ($this->isGeneral()) {
            return 1;
        }

        if ($this->product_type === self::TYPE_LIQUID) {
            return max(1, (int) $this->units_per_box);
        }

        return max(1, (int) $this->tablets_per_strip) * max(1, (int) $this->strips_per_box);
    }

    public function stockBoxes(): float
    {
        return $this->isGeneral()
            ? (float) $this->stock
            : ((float) $this->stock) / $this->unitsPerBox();
    }

    public function lowStockBoxes(): float
    {
        return $this->isGeneral()
            ? (float) ($this->low_stock_threshold ?? 0)
            : ((float) ($this->low_stock_threshold ?? 0)) / $this->unitsPerBox();
    }

    public function formattedStock(): string
    {
        $qty = $this->stockBoxes();
        $formatted = number_format($qty, floor($qty) == $qty ? 0 : 2);

        return $formatted . ' ' . ($this->isGeneral() ? strtoupper($this->unit ?? 'pcs') : 'Boxes');
    }

    public function stripSalePrice(): float
    {
        return round(((float) $this->price) * max(1, (int) $this->tablets_per_strip), 2);
    }

    public function boxSalePrice(): float
    {
        if ($this->product_type === self::TYPE_LIQUID) {
            return round(((float) $this->price) * $this->unitsPerBox(), 2);
        }
        return round($this->stripSalePrice() * max(1, (int) $this->strips_per_box), 2);
    }

    public function stripBuyPrice(): float
    {
        return round(((float) $this->buy_price) * max(1, (int) $this->tablets_per_strip), 2);
    }

    public function boxBuyPrice(): float
    {
        if ($this->product_type === self::TYPE_LIQUID) {
            return round(((float) $this->buy_price) * $this->unitsPerBox(), 2);
        }
        return round($this->stripBuyPrice() * max(1, (int) $this->strips_per_box), 2);
    }

    public function isGeneral(): bool
    {
        return $this->product_type === self::TYPE_GENERAL;
    }

    // ── Expiry Helpers ─────────────────────────────────────────────

    /** Returns: 'expired' | 'red' (<1 month) | 'yellow' (1-3 months) | 'orange' (3-6 months) | 'green' (>6 months) | null */
    public function expiryStatus(): ?string
    {
        if (!$this->expiry_date) return null;
        $today = Carbon::today();
        if ($this->expiry_date->lt($today))                         return 'expired';
        if ($this->expiry_date->lt($today->copy()->addMonth()))     return 'red';
        if ($this->expiry_date->lt($today->copy()->addMonths(3)))   return 'yellow';
        if ($this->expiry_date->lt($today->copy()->addMonths(6)))   return 'orange';
        return 'green';
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->lt(Carbon::today());
    }

    public function expiryBadgeHtml(): string
    {
        if (!$this->expiry_date) return '';
        $status = $this->expiryStatus();
        $label  = $this->expiry_date->format('d M Y');
        $map = [
            'expired' => 'danger',
            'red'     => 'danger',
            'yellow'  => 'warning',
            'orange'  => 'warning',
            'green'   => 'success',
        ];
        $cls = $map[$status] ?? 'secondary';
        $icon = $status === 'expired' ? '🔒 ' : '';
        return "<span class=\"badge bg-{$cls}\">{$icon}{$label}</span>";
    }

    // ── Scopes ─────────────────────────────────────────────────────
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expiry_date')
              ->orWhere('expiry_date', '>=', Carbon::today());
        });
    }

    public function scopeExpiringWithin($query, int $days)
    {
        return $query->whereNotNull('expiry_date')
                     ->where('expiry_date', '>=', Carbon::today())
                     ->where('expiry_date', '<=', Carbon::today()->addDays($days));
    }
}
