<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BusinessSettingController extends Controller
{
    public function edit()
    {
        $businessSetting = BusinessSetting::current();
        return view('settings.business', compact('businessSetting'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'business_name'        => 'nullable|string|max:255',
            'phone'                => 'nullable|string|max:50',
            'delivery_phone'       => 'nullable|string|max:50',
            'address'              => 'nullable|string|max:500',
            'sales_tax_rate'       => 'nullable|numeric|min:0|max:100',
            'service_fee'          => 'nullable|numeric|min:0|max:9999999999',
            'fbr_invoice_prefix'   => 'nullable|string|max:20',
            'receipt_back_heading' => 'nullable|string|max:255',
            'receipt_back_notes'   => 'nullable|string|max:5000',
            'receipt_back_rtl'     => 'nullable|boolean',
            'logo'                 => 'nullable|image|max:2048',
            'remove_logo'          => 'nullable|boolean',
            'back_logo'            => 'nullable|image|max:2048',
            'remove_back_logo'     => 'nullable|boolean',
            // Header toggles
            'license_no'           => 'nullable|string|max:100',
            'ntn_no'               => 'nullable|string|max:50',
            'strn_no'              => 'nullable|string|max:50',
            'show_license'         => 'nullable|boolean',
            'show_ntn'             => 'nullable|boolean',
            'show_strn'            => 'nullable|boolean',
            'show_phone_on_print'  => 'nullable|boolean',
        ]);

        $businessSetting = BusinessSetting::current();

        // Handle main logo
        if ($request->boolean('remove_logo') && $businessSetting->logo_path) {
            Storage::disk('public')->delete($businessSetting->logo_path);
            $data['logo_path'] = null;
        }
        if ($request->hasFile('logo')) {
            if ($businessSetting->logo_path) {
                Storage::disk('public')->delete($businessSetting->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('business', 'public');
        }

        // Handle back logo
        if ($request->boolean('remove_back_logo') && $businessSetting->back_logo_path) {
            Storage::disk('public')->delete($businessSetting->back_logo_path);
            $data['back_logo_path'] = null;
        }
        if ($request->hasFile('back_logo')) {
            if ($businessSetting->back_logo_path) {
                Storage::disk('public')->delete($businessSetting->back_logo_path);
            }
            $data['back_logo_path'] = $request->file('back_logo')->store('business', 'public');
        }

        unset($data['logo'], $data['remove_logo'], $data['back_logo'], $data['remove_back_logo']);

        // Defaults and coercions
        $data['sales_tax_rate']      = $data['sales_tax_rate'] ?? 0;
        $data['service_fee']         = $data['service_fee'] ?? 0;
        $data['show_license']        = $request->boolean('show_license');
        $data['show_ntn']            = $request->boolean('show_ntn');
        $data['show_strn']           = $request->boolean('show_strn');
        $data['show_phone_on_print'] = $request->boolean('show_phone_on_print');
        $data['receipt_back_rtl']    = $request->boolean('receipt_back_rtl');

        $businessSetting->fill($data)->save();

        return redirect()->route('business-settings.edit')->with('success', 'Business settings updated successfully!');
    }

    public function cleanupSelectedData(Request $request)
    {
        $cleanupTypes = [
            'billing_invoices' => 'Billing invoices',
            'held_bills' => 'Held bills',
            'sales_returns' => 'Sales returns',
            'purchases' => 'Local distributor purchases / khata',
            'supplier_payments' => 'Supplier payments / kisht entries',
            'customer_payments' => 'Customer collections',
            'expenses' => 'Expenses',
            'cash_registers' => 'Cash register shifts',
            'stock_returns' => 'Returns / damaged stock',
            'cash_to_bank_logs' => 'Cash to bank logs',
        ];

        $data = $request->validate([
            'cleanup_scope' => 'required|string|in:older_than,all_selected',
            'cutoff_date' => 'required_if:cleanup_scope,older_than|nullable|date',
            'data_types' => 'required|array|min:1',
            'data_types.*' => ['required', 'string', Rule::in(array_keys($cleanupTypes))],
            'confirm_cleanup' => 'required|string|in:DELETE SELECTED DATA',
        ]);

        $deleteAll = $data['cleanup_scope'] === 'all_selected';
        $cutoffDate = $data['cutoff_date'] ?? null;
        $selectedTypes = array_values(array_unique($data['data_types']));
        $deleted = [];

        $dateFilter = function ($query, string $column) use ($deleteAll, $cutoffDate) {
            return $deleteAll ? $query : $query->whereDate($column, '<=', $cutoffDate);
        };

        DB::transaction(function () use ($selectedTypes, $dateFilter, &$deleted) {
            if (in_array('billing_invoices', $selectedTypes, true)) {
                $invoiceIds = $dateFilter(DB::table('invoices'), 'invoice_date')->pluck('id');
                $deleted['Invoice items'] = $invoiceIds->isEmpty() ? 0 : DB::table('invoice_items')->whereIn('invoice_id', $invoiceIds)->delete();
                $deleted['Billing invoices'] = $invoiceIds->isEmpty() ? 0 : DB::table('invoices')->whereIn('id', $invoiceIds)->delete();
            }

            if (in_array('held_bills', $selectedTypes, true)) {
                $deleted['Held bills'] = $dateFilter(DB::table('held_invoices'), 'created_at')->delete();
            }

            if (in_array('sales_returns', $selectedTypes, true)) {
                $deleted['Sales returns'] = $dateFilter(DB::table('customer_returns'), 'created_at')->delete();
            }

            if (in_array('purchases', $selectedTypes, true)) {
                $purchaseIds = $dateFilter(DB::table('purchases'), 'purchase_date')->pluck('id');
                $deleted['Purchase items'] = $purchaseIds->isEmpty() ? 0 : DB::table('purchase_items')->whereIn('purchase_id', $purchaseIds)->delete();
                $deleted['Linked supplier payments'] = $purchaseIds->isEmpty() ? 0 : DB::table('supplier_payments')->whereIn('purchase_id', $purchaseIds)->delete();
                $deleted['Purchases'] = $purchaseIds->isEmpty() ? 0 : DB::table('purchases')->whereIn('id', $purchaseIds)->delete();
            }

            if (in_array('supplier_payments', $selectedTypes, true)) {
                $deleted['Supplier payments'] = $dateFilter(DB::table('supplier_payments'), 'payment_date')->delete();
            }

            if (in_array('customer_payments', $selectedTypes, true)) {
                $deleted['Customer collections'] = $dateFilter(DB::table('customer_payments'), 'payment_date')->delete();
            }

            if (in_array('expenses', $selectedTypes, true)) {
                $deleted['Expenses'] = $dateFilter(DB::table('expenses'), 'expense_date')->delete();
            }

            if (in_array('cash_registers', $selectedTypes, true)) {
                $deleted['Cash register shifts'] = $dateFilter(DB::table('cash_registers'), 'opened_at')->delete();
            }

            if (in_array('stock_returns', $selectedTypes, true)) {
                $deleted['Returns / damaged stock'] = $dateFilter(DB::table('stock_returns'), 'created_at')->delete();
            }

            if (in_array('cash_to_bank_logs', $selectedTypes, true) && Schema::hasTable('cash_to_bank_logs')) {
                $deleted['Cash to bank logs'] = $dateFilter(DB::table('cash_to_bank_logs'), 'created_at')->delete();
            }
        });

        $totalDeleted = array_sum($deleted);
        $scopeText = $deleteAll ? 'all selected records' : 'records up to ' . \Carbon\Carbon::parse($cutoffDate)->format('d M Y');
        $summary = collect($deleted)
            ->filter(fn($count) => $count > 0)
            ->map(fn($count, $label) => "{$label}: {$count}")
            ->implode(', ');

        $message = $totalDeleted > 0
            ? "Deleted {$totalDeleted} {$scopeText}. {$summary}"
            : "No matching records found for the selected cleanup.";

        return redirect()->route('business-settings.edit')->with('success', $message);
    }
}
