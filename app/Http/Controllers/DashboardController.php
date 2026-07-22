<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalInvoices  = Invoice::count();
        $totalRevenue   = Invoice::where('status', 'paid')->sum('total');
        $totalProducts  = Product::count();
        $totalCustomers = Customer::count();

        $recentInvoices   = Invoice::with('customer')->latest()->limit(10)->get();
        $lowStockProducts = Product::with('category')
            ->whereRaw('stock <= low_stock_threshold')
            ->orderBy('stock')
            ->limit(8)
            ->get();

        // Last 7 days sales chart data
        $salesChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $salesChart[] = [
                'date'    => $date->format('d M'),
                'revenue' => Invoice::where('status', 'paid')
                    ->whereDate('created_at', $date)
                    ->sum('total'),
            ];
        }

        // Top 5 selling products (by qty)
        $topProducts = InvoiceItem::select('product_id', DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(total) as total_revenue'))
            ->with('product')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // Pending / Due invoices (credit/udhar)
        $pendingInvoices = Invoice::with('customer')
            ->where('due_amount', '>', 0)
            ->latest()
            ->limit(8)
            ->get();
        $totalPending = Invoice::sum('due_amount');

        // Total expenses this month
        $totalExpenses = Expense::whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        // ── Expiry Alerts ──────────────────────────────────────────
        $expiredProducts = Product::with('category')
            ->where('stock', '>', 0)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', Carbon::today())
            ->orderBy('expiry_date')
            ->get();

        $expiringProducts = Product::with('category')
            ->where('stock', '>', 0)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>=', Carbon::today())
            ->where('expiry_date', '<=', Carbon::today()->addDays(60))
            ->orderBy('expiry_date')
            ->get();

        $showExpiryPopup = $expiredProducts->isNotEmpty() || $expiringProducts->isNotEmpty();

        // ── Cashier-wise Sales Analytics (Admin only) ──────────────
        $cashierSales = collect();
        if (auth()->user()->isAdmin()) {
            $cashierSales = User::where('role', 'cashier')
                ->get()
                ->map(function ($cashier) {
                    $invoices = Invoice::where('cashier_id', $cashier->id)->where('status', 'paid');
                    $today    = Invoice::where('cashier_id', $cashier->id)->where('status', 'paid')
                        ->whereDate('created_at', today());
                    $month    = Invoice::where('cashier_id', $cashier->id)->where('status', 'paid')
                        ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                    return (object) [
                        'id'              => $cashier->id,
                        'name'            => $cashier->name,
                        'total_invoices'  => $invoices->count(),
                        'total_revenue'   => $invoices->sum('total'),
                        'today_revenue'   => $today->sum('total'),
                        'today_invoices'  => $today->count(),
                        'month_revenue'   => $month->sum('total'),
                        'month_invoices'  => $month->count(),
                    ];
                })
                ->sortByDesc('total_revenue');
        }

        return view('dashboard.index', compact(
            'totalInvoices', 'totalRevenue', 'totalProducts', 'totalCustomers',
            'recentInvoices', 'lowStockProducts', 'salesChart', 'topProducts',
            'pendingInvoices', 'totalPending', 'totalExpenses',
            'expiredProducts', 'expiringProducts', 'showExpiryPopup',
            'cashierSales'
        ));
    }
}
