<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BusinessSetting extends Model
{
    use HasFactory;

    public const SOFTWARE_CREDIT = 'Computer Software developed by TRITEC Abdul Kareem Ph No 03196410725';

    protected $fillable = [
        'business_name',
        'phone',
        'address',
        'logo_path',
        'back_logo_path',
        'delivery_phone',
        'sales_tax_rate',
        'service_fee',
        'fbr_invoice_prefix',
        'receipt_back_heading',
        'receipt_back_notes',
        'receipt_back_rtl',
        // Header toggles
        'license_no',
        'ntn_no',
        'strn_no',
        'show_license',
        'show_ntn',
        'show_strn',
        'show_phone_on_print',
        'cash_in_hand',
        'bank_balance',
    ];

    protected $casts = [
        'sales_tax_rate'      => 'decimal:2',
        'service_fee'         => 'decimal:2',
        'show_license'        => 'boolean',
        'show_ntn'            => 'boolean',
        'show_strn'           => 'boolean',
        'show_phone_on_print' => 'boolean',
        'receipt_back_rtl'    => 'boolean',
        'cash_in_hand'        => 'decimal:2',
        'bank_balance'        => 'decimal:2',
    ];

    public static function current(): self
    {
        try {
            if (!Schema::hasTable('business_settings')) {
                return self::defaults();
            }
            return self::query()->first() ?? self::query()->create(self::defaultAttributes());
        } catch (Throwable) {
            return self::defaults();
        }
    }

    public static function defaultAttributes(): array
    {
        return [
            'business_name'        => 'Pharmacy POS',
            'phone'                => '0300-1234567',
            'address'              => 'Your Pharmacy Address',
            'logo_path'            => null,
            'back_logo_path'       => null,
            'delivery_phone'       => '0300-1234567',
            'sales_tax_rate'       => 0,
            'service_fee'          => 0,
            'fbr_invoice_prefix'   => 'FBR',
            'receipt_back_heading' => 'توجہ فرمائیں',
            'receipt_back_notes'   => "اپنا سامان وصول کرنے کے بعد چیک کریں۔\nادویات ڈاکٹر کے مشورے کے مطابق استعمال کریں۔\nرسید کے بغیر واپسی قابلِ قبول نہیں ہوگی۔\nمزید معلومات کے لیے ہمارے اسٹور سے رابطہ کریں۔",
            'receipt_back_rtl'     => true,
            'license_no'           => null,
            'ntn_no'               => null,
            'strn_no'              => null,
            'show_license'         => false,
            'show_ntn'             => false,
            'show_strn'            => false,
            'show_phone_on_print'  => true,
        ];
    }

    private static function defaults(): self
    {
        return new self(self::defaultAttributes());
    }
}
