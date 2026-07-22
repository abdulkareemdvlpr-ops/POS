<?php

use App\Models\BusinessSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('business_settings')) {
            return;
        }

        $defaults = BusinessSetting::defaultAttributes();

        DB::table('business_settings')
            ->where(function ($query) {
                $query->whereNull('business_name')
                    ->orWhere('business_name', '')
                    ->orWhere('business_name', 'POS System');
            })
            ->update(['business_name' => $defaults['business_name']]);

        DB::table('business_settings')
            ->where(function ($query) {
                $query->whereNull('address')
                    ->orWhere('address', '')
                    ->orWhere('address', 'Your Business Address');
            })
            ->update(['address' => $defaults['address']]);

        DB::table('business_settings')
            ->where(function ($query) {
                $query->whereNull('delivery_phone')->orWhere('delivery_phone', '');
            })
            ->update(['delivery_phone' => DB::raw("COALESCE(NULLIF(phone, ''), '0300-1234567')")]);

        foreach ([
            'sales_tax_rate',
            'service_fee',
        ] as $column) {
            if (!Schema::hasColumn('business_settings', $column)) {
                continue;
            }

            DB::table('business_settings')
                ->whereNull($column)
                ->update([$column => $defaults[$column]]);
        }

        foreach ([
            'fbr_invoice_prefix',
            'receipt_back_heading',
            'receipt_back_notes',
        ] as $column) {
            if (!Schema::hasColumn('business_settings', $column)) {
                continue;
            }

            DB::table('business_settings')
                ->where(function ($query) use ($column) {
                    $query->whereNull($column)->orWhere($column, '');
                })
                ->update([$column => $defaults[$column]]);
        }
    }

    public function down(): void
    {
        //
    }
};
