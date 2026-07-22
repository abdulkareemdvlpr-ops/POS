<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // ── End of Day Reconciliation (Admin) ──────────────────────────
    public function eod(Request $request)
    {
        $date = $request->filled('date') ? $request->date : today()->toDateString();

        $invoices = Invoice::whereDate('invoice_date', $date)
            ->with('cashier', 'customer', 'items')
            ->orderBy('id')
            ->get();

        $registers = \App\Models\CashRegister::whereDate('opened_at', $date)
            ->with('user')
            ->orderBy('id')
            ->get();

        $summary = [
            'cash'          => $invoices->where('payment_method', 'cash')->where('status','paid')->sum('total'),
            'card'          => $invoices->where('payment_method', 'card')->where('status','paid')->sum('total'),
            'bank_transfer' => $invoices->where('payment_method', 'bank_transfer')->where('status','paid')->sum('total'),
            'credit'        => $invoices->where('status','pending')->sum('total'),
            'total_paid'    => $invoices->where('status','paid')->sum('total'),
            'total'         => $invoices->sum('total'),
            'count'         => $invoices->count(),
        ];

        $expenses = Expense::whereDate('expense_date', $date)->sum('amount');
        $netCash  = $summary['cash'] + $summary['card'] + $summary['bank_transfer'] - $expenses;

        // Cashier-wise breakdown for EOD
        $cashierBreakdown = $invoices->groupBy('cashier_id')->map(function ($group, $cashierId) {
            $cashier = $group->first()->cashier;
            return [
                'name'    => $cashier->name ?? 'Unknown',
                'count'   => $group->count(),
                'revenue' => $group->where('status','paid')->sum('total'),
                'pending' => $group->where('status','pending')->sum('total'),
            ];
        })->values();

        return view('reports.eod', compact('invoices', 'summary', 'expenses', 'netCash', 'date', 'cashierBreakdown', 'registers'));
    }

    // ── Cashier EOD (Cashier self-service) ─────────────────────────
    public function cashierEod(Request $request)
    {
        $date     = $request->filled('date') ? $request->date : today()->toDateString();
        $cashierId = auth()->user()->isAdmin()
            ? ($request->filled('cashier_id') ? $request->cashier_id : null)
            : auth()->id();

        $query = Invoice::whereDate('invoice_date', $date)->with('customer', 'items');
        if ($cashierId) {
            $query->where('cashier_id', $cashierId);
        }
        $invoices = $query->orderBy('id')->get();

        $cashSales = $invoices->where('payment_method', 'cash')->sum('paid_amount');
        
        $customerPaymentsQuery = \App\Models\CustomerPayment::whereDate('payment_date', $date)->where('payment_method', 'cash');
        if ($cashierId) {
            $customerPaymentsQuery->where('created_by', $cashierId);
        }
        $customerCollections = $customerPaymentsQuery->sum('amount');

        $supplierPaymentsQuery = \App\Models\SupplierPayment::whereDate('payment_date', $date)->where('payment_method', 'cash');
        if ($cashierId) {
            $supplierPaymentsQuery->where('created_by', $cashierId);
        }
        $supplierPayouts = $supplierPaymentsQuery->sum('amount');

        $netDrawerCash = $cashSales + $customerCollections - $supplierPayouts;

        $summary = [
            'count'      => $invoices->count(),
            'total_paid' => $invoices->sum('paid_amount'),
            'cash'       => $cashSales,
            'card'       => $invoices->where('payment_method', 'card')->sum('paid_amount'),
            'credit'     => $invoices->sum('due_amount'),
            'customer_collections' => $customerCollections,
            'supplier_payouts'     => $supplierPayouts,
            'net_drawer_cash'      => $netDrawerCash,
        ];

        $cashiers = auth()->user()->isAdmin() ? User::where('role','cashier')->get() : collect();
        $selectedCashier = $cashierId ? User::find($cashierId) : null;

        return view('reports.cashier-eod', compact('invoices','summary','date','cashiers','selectedCashier','cashierId'));
    }

    // ── Tax / GST Audit ────────────────────────────────────────────
    public function tax(Request $request)
    {
        $from = $request->filled('from_date') ? $request->from_date : now()->startOfMonth()->toDateString();
        $to   = $request->filled('to_date')   ? $request->to_date   : now()->toDateString();

        $invoices = $this->applyDateFilter(Invoice::query(), 'invoice_date', $from, $to)
            ->where('status', 'paid')
            ->where('tax', '>', 0)
            ->with('customer', 'cashier', 'items.product')
            ->orderBy('id')
            ->get()
            ->map(function ($inv) {
                $discountAmt = $inv->discount_type === 'percent'
                    ? ($inv->subtotal * $inv->discount / 100)
                    : $inv->discount;
                $taxable         = max(0, $inv->subtotal - $discountAmt);
                $taxAmount       = $taxable * $inv->tax / 100;
                $inv->tax_amount = $taxAmount;
                $inv->taxable_amount = $taxable;
                return $inv;
            });

        $totals = [
            'taxable'    => $invoices->sum('taxable_amount'),
            'tax_amount' => $invoices->sum('tax_amount'),
            'total'      => $invoices->sum('total'),
        ];

        return view('reports.tax', compact('invoices', 'totals', 'from', 'to'));
    }

    // ── Profit / Loss ──────────────────────────────────────────────
    public function profitLoss(Request $request)
    {
        $from = $request->filled('from_date') ? $request->from_date : now()->startOfMonth()->toDateString();
        $to   = $request->filled('to_date')   ? $request->to_date   : now()->toDateString();

        $items = InvoiceItem::select(
                'product_id',
                DB::raw('SUM(qty) as total_qty'),
                DB::raw('SUM(qty * price) as total_revenue')
            )
            ->with(['product' => fn($q) => $q->select('id','name','buy_price','category_id')->with('category:id,name')])
            ->whereHas('invoice', fn($q) => $this->applyDateFilter($q, 'invoice_date', $from, $to)->where('status','paid'))
            ->groupBy('product_id')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(function ($item) {
                $buyPrice     = (float) ($item->product->buy_price ?? 0);
                $item->cost   = $buyPrice * $item->total_qty;
                $item->profit = $item->total_revenue - $item->cost;
                $item->margin = $item->total_revenue > 0 ? round($item->profit / $item->total_revenue * 100, 1) : 0;
                return $item;
            });

        $expenses = $this->applyDateFilter(Expense::query(), 'expense_date', $from, $to)->sum('amount');

        $totals = [
            'revenue'  => $items->sum('total_revenue'),
            'cost'     => $items->sum('cost'),
            'gross'    => $items->sum('profit'),
            'expenses' => $expenses,
            'net'      => $items->sum('profit') - $expenses,
        ];

        $dailyChart = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $dayRevenue = InvoiceItem::whereHas('invoice', fn($q) =>
                    $q->whereDate('invoice_date', $day)->where('status','paid'))
                ->sum(DB::raw('qty * price'));
            $dayCost = InvoiceItem::whereHas('invoice', fn($q) =>
                    $q->whereDate('invoice_date', $day)->where('status','paid'))
                ->join('products', 'products.id', '=', 'invoice_items.product_id')
                ->sum(DB::raw('invoice_items.qty * products.buy_price'));

            $dailyChart[] = [
                'date'    => $day->format('d M'),
                'revenue' => round($dayRevenue, 2),
                'cost'    => round($dayCost, 2),
                'profit'  => round($dayRevenue - $dayCost, 2),
            ];
        }

        return view('reports.profit-loss', compact('items', 'totals', 'from', 'to', 'dailyChart'));
    }

    // ── Helper: date range from request ─────────────────────────────
    private function dateRange(Request $request, $defaultDays = 30)
    {
        $from = $request->filled('from_date') ? $request->from_date : Carbon::today()->subDays($defaultDays)->toDateString();
        $to   = $request->filled('to_date')   ? $request->to_date   : Carbon::today()->toDateString();
        return [$from, $to];
    }

    // ── Helper: apply date filter — MySQL + SQLite safe ───────────────
    // Uses whereBetween with explicit timestamps so both engines agree.
    private function applyDateFilter($query, string $column, string $from, string $to)
    {
        // "2026-07-01 00:00:00"  ..  "2026-07-15 23:59:59"
        return $query->whereBetween($column, [$from . ' 00:00:00', $to . ' 23:59:59']);
    }

    // ── Customer Register (list of customers with balances) ────────
    public function customerRegister(Request $request)
    {
        $customers = \App\Models\Customer::withSum('invoices as total_sales', 'total')
            ->withSum('invoices as total_due', 'due_amount')
            ->withSum('payments as total_paid', 'amount')
            ->orderBy('name')
            ->get();

        $rows = $customers->map(fn($c) => [
            'name'    => $c->name,
            'phone'   => $c->phone,
            'city'    => $c->city ?? '-',
            'sales'   => 'Rs. ' . number_format($c->total_sales ?? 0, 2),
            'paid'    => 'Rs. ' . number_format($c->total_paid ?? 0, 2),
            'due'     => 'Rs. ' . number_format($c->total_due ?? 0, 2),
            'status'  => $c->status ? 'Active' : 'Inactive',
        ])->all();

        return view('reports.list', [
            'title' => 'Customer Register',
            'columns' => ['name'=>'Name','phone'=>'Phone','city'=>'City','sales'=>'Total Sales','paid'=>'Total Paid','due'=>'Balance Due','status'=>'Status'],
            'rows' => $rows,
            'showDateFilter' => false,
        ]);
    }

    // ── Customer Sale Products (products bought per customer) ──────
    public function customerSaleProducts(Request $request)
    {
        [$from, $to] = $this->dateRange($request, 30);

        $items = InvoiceItem::select('product_id', DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(total) as total_amount'))
            ->with('product:id,name')
            ->whereHas('invoice', function ($q) use ($from, $to, $request) {
                $this->applyDateFilter($q, 'invoice_date', $from, $to);
                if ($request->filled('customer_id')) $q->where('customer_id', $request->customer_id);
            })
            ->groupBy('product_id')
            ->orderByDesc('total_amount')
            ->get();

        $rows = $items->map(fn($i) => [
            'product' => $i->product->name ?? 'Unknown',
            'qty'     => $i->total_qty,
            'amount'  => 'Rs. ' . number_format($i->total_amount, 2),
        ])->all();

        return view('reports.list', [
            'title' => 'Customer Sale Products',
            'columns' => ['product'=>'Product','qty'=>'Qty Sold','amount'=>'Total Amount'],
            'rows' => $rows,
            'from' => $from, 'to' => $to,
        ]);
    }

    // ── Purchase History ─────────────────────────────────────────────
    public function purchaseHistory(Request $request)
    {
        [$from, $to] = $this->dateRange($request, 30);

        $purchases = $this->applyDateFilter(\App\Models\Purchase::with('supplier'), 'purchase_date', $from, $to)
            ->orderByDesc('purchase_date')
            ->get();

        $rows = $purchases->map(fn($p) => [
            'date'     => $p->purchase_date ? \Carbon\Carbon::parse($p->purchase_date)->format('d M Y') : '-',
            'supplier' => $p->supplier->name ?? '-',
            'total'    => 'Rs. ' . number_format($p->total_amount, 2),
            'paid'     => 'Rs. ' . number_format($p->paid_amount, 2),
            'due'      => 'Rs. ' . number_format($p->due_amount, 2),
            'status'   => ucfirst($p->payment_status),
        ])->all();

        return view('reports.list', [
            'title' => 'Purchase History',
            'columns' => ['date'=>'Date','supplier'=>'Supplier','total'=>'Total','paid'=>'Paid','due'=>'Due','status'=>'Status'],
            'rows' => $rows,
            'from' => $from, 'to' => $to,
            'totals' => ['total' => 'Rs. '.number_format($purchases->sum('total_amount'),2), 'paid' => 'Rs. '.number_format($purchases->sum('paid_amount'),2), 'due' => 'Rs. '.number_format($purchases->sum('due_amount'),2)],
        ]);
    }

    // ── Purchase Return History (Stock Returns to suppliers) ────────
    public function purchaseReturnHistory(Request $request)
    {
        [$from, $to] = $this->dateRange($request, 30);

        $returns = $this->applyDateFilter(\App\Models\StockReturn::with('product', 'supplier', 'user'), 'return_date', $from, $to)
            ->orderByDesc('return_date')
            ->get();

        $rows = $returns->map(fn($r) => [
            'date'     => $r->return_date ? \Carbon\Carbon::parse($r->return_date)->format('d M Y') : '-',
            'product'  => $r->product->name ?? '-',
            'supplier' => $r->supplier->name ?? '-',
            'qty'      => $r->qty,
            'reason'   => ucfirst(str_replace('_', ' ', $r->reason)),
            'status'   => ucfirst($r->status),
        ])->all();

        return view('reports.list', [
            'title' => 'Purchase Return History',
            'columns' => ['date'=>'Date','product'=>'Product','supplier'=>'Supplier','qty'=>'Qty','reason'=>'Reason','status'=>'Status'],
            'rows' => $rows,
            'from' => $from, 'to' => $to,
        ]);
    }

    // ── Product Purchase History (purchases grouped by product) ────
    public function productPurchaseHistory(Request $request)
    {
        [$from, $to] = $this->dateRange($request, 30);

        $items = \App\Models\PurchaseItem::select('product_id', DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(total) as total_amount'), DB::raw('AVG(cost_price) as avg_cost'))
            ->with('product:id,name')
            ->whereHas('purchase', fn($q) => $this->applyDateFilter($q, 'purchase_date', $from, $to))
            ->groupBy('product_id')
            ->orderByDesc('total_amount')
            ->get();

        $rows = $items->map(fn($i) => [
            'product'   => $i->product->name ?? 'Unknown',
            'qty'       => $i->total_qty,
            'avg_cost'  => 'Rs. ' . number_format($i->avg_cost, 2),
            'amount'    => 'Rs. ' . number_format($i->total_amount, 2),
        ])->all();

        return view('reports.list', [
            'title' => 'Product Purchase History',
            'columns' => ['product'=>'Product','qty'=>'Qty Purchased','avg_cost'=>'Avg Cost Price','amount'=>'Total Amount'],
            'rows' => $rows,
            'from' => $from, 'to' => $to,
        ]);
    }

    // ── Sale History (invoice list) ─────────────────────────────────
    public function saleHistory(Request $request)
    {
        [$from, $to] = $this->dateRange($request, 30);

        $invoices = $this->applyDateFilter(Invoice::with('customer', 'cashier'), 'invoice_date', $from, $to)
            ->orderByDesc('invoice_date')
            ->get();

        $rows = $invoices->map(fn($i) => [
            'date'     => $i->invoice_date ? $i->invoice_date->format('d M Y') : '-',
            'customer' => $i->customer->name ?? 'Walk-in',
            'cashier'  => $i->cashier->name ?? '-',
            'total'    => 'Rs. ' . number_format($i->total, 2),
            'status'   => ucfirst($i->status),
        ])->all();

        return view('reports.list', [
            'title' => 'Sale History',
            'columns' => ['date'=>'Date','customer'=>'Customer','cashier'=>'Cashier','total'=>'Total','status'=>'Status'],
            'rows' => $rows,
            'from' => $from, 'to' => $to,
            'totals' => ['total' => 'Rs. '.number_format($invoices->sum('total'),2)],
        ]);
    }

    // ── User (Cashier) Sale History ──────────────────────────────────
    public function userSaleHistory(Request $request)
    {
        [$from, $to] = $this->dateRange($request, 30);

        $data = $this->applyDateFilter(
                Invoice::select('cashier_id', DB::raw('COUNT(*) as bills'), DB::raw('SUM(total) as total_sales')),
                'invoice_date', $from, $to
            )
            ->with('cashier:id,name')
            ->groupBy('cashier_id')
            ->orderByDesc('total_sales')
            ->get();

        $rows = $data->map(fn($d) => [
            'user'   => $d->cashier->name ?? 'Unknown',
            'bills'  => $d->bills,
            'total'  => 'Rs. ' . number_format($d->total_sales, 2),
        ])->all();

        return view('reports.list', [
            'title' => 'User Sale History',
            'columns' => ['user'=>'User','bills'=>'Bills','total'=>'Total Sales'],
            'rows' => $rows,
            'from' => $from, 'to' => $to,
        ]);
    }

    // ── Shift Sale History (sales grouped by cash register shift) ──
    public function shiftSaleHistory(Request $request)
    {
        [$from, $to] = $this->dateRange($request, 30);

        $registers = $this->applyDateFilter(\App\Models\CashRegister::with('user'), 'opened_at', $from, $to)
            ->orderByDesc('opened_at')
            ->get();

        // Fetch all invoices touching this window once, then match them to shifts
        // in memory to avoid running one query per register row.
        $windowStart = $registers->min('opened_at') ?? now();
        $invoices = Invoice::where('created_at', '>=', $windowStart)
            ->get(['id', 'cashier_id', 'total', 'created_at']);

        $rows = $registers->map(function ($r) use ($invoices) {
            $end = $r->closed_at ?? now();
            $sales = $invoices
                ->where('cashier_id', $r->user_id)
                ->filter(fn($inv) => $inv->created_at->between($r->opened_at, $end))
                ->sum('total');
            return [
                'user'   => $r->user->name ?? '-',
                'opened' => $r->opened_at->format('d M Y H:i'),
                'closed' => $r->closed_at?->format('d M Y H:i') ?? 'Open',
                'sales'  => 'Rs. ' . number_format($sales, 2),
                'status' => ucfirst($r->status),
            ];
        })->all();

        return view('reports.list', [
            'title' => 'Shift Sale History',
            'columns' => ['user'=>'User','opened'=>'Opened At','closed'=>'Closed At','sales'=>'Total Sales','status'=>'Status'],
            'rows' => $rows,
            'from' => $from, 'to' => $to,
        ]);
    }

    // ── Category Sale History ────────────────────────────────────────
    public function categorySaleHistory(Request $request)
    {
        [$from, $to] = $this->dateRange($request, 30);

        $items = InvoiceItem::select('product_id', 'qty', 'total')
            ->with('product:id,name,category_id')
            ->whereHas('invoice', fn($q) => $this->applyDateFilter($q, 'invoice_date', $from, $to))
            ->get()
            ->groupBy(fn($i) => $i->product->category_id ?? 0);

        $categories = \App\Models\Category::pluck('name', 'id');

        $rows = $items->map(function ($group, $catId) use ($categories) {
            return [
                'category' => $categories[$catId] ?? 'Uncategorized',
                'qty'      => $group->sum('qty'),
                'amount'   => 'Rs. ' . number_format($group->sum('total'), 2),
            ];
        })->sortByDesc(fn($r) => (float) str_replace(['Rs. ', ','], '', $r['amount']))->values()->all();

        return view('reports.list', [
            'title' => 'Category Sale History',
            'columns' => ['category'=>'Category','qty'=>'Qty Sold','amount'=>'Total Amount'],
            'rows' => $rows,
            'from' => $from, 'to' => $to,
        ]);
    }

    // ── Product Sale History ─────────────────────────────────────────
    public function productSaleHistory(Request $request)
    {
        [$from, $to] = $this->dateRange($request, 30);

        $items = InvoiceItem::select('product_id', DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(total) as total_amount'))
            ->with('product:id,name')
            ->whereHas('invoice', fn($q) => $this->applyDateFilter($q, 'invoice_date', $from, $to))
            ->groupBy('product_id')
            ->orderByDesc('total_amount')
            ->get();

        $rows = $items->map(fn($i) => [
            'product' => $i->product->name ?? 'Unknown',
            'qty'     => $i->total_qty,
            'amount'  => 'Rs. ' . number_format($i->total_amount, 2),
        ])->all();

        return view('reports.list', [
            'title' => 'Product Sale History',
            'columns' => ['product'=>'Product','qty'=>'Qty Sold','amount'=>'Total Amount'],
            'rows' => $rows,
            'from' => $from, 'to' => $to,
        ]);
    }

    // ── Sale History (Category-wise, detailed / "Pro") ──────────────
    public function saleHistoryCategoryPro(Request $request)
    {
        [$from, $to] = $this->dateRange($request, 30);

        $items = InvoiceItem::with(['product:id,name,category_id', 'invoice:id,invoice_date,customer_id'])
            ->whereHas('invoice', fn($q) => $this->applyDateFilter($q, 'invoice_date', $from, $to))
            ->get();

        $categories = \App\Models\Category::pluck('name', 'id');

        $rows = $items->map(fn($i) => [
            'date'     => $i->invoice->invoice_date ? $i->invoice->invoice_date->format('d M Y') : '-',
            'category' => $categories[$i->product->category_id ?? 0] ?? 'Uncategorized',
            'product'  => $i->product->name ?? 'Unknown',
            'qty'      => $i->qty,
            'amount'   => 'Rs. ' . number_format($i->total, 2),
        ])->sortBy('category')->values()->all();

        return view('reports.list', [
            'title' => 'Category / Product Sale Detail',
            'columns' => ['date'=>'Date','category'=>'Category','product'=>'Product','qty'=>'Qty','amount'=>'Amount'],
            'rows' => $rows,
            'from' => $from, 'to' => $to,
        ]);
    }

    // ── Customer Sale History ────────────────────────────────────────
    public function customerSaleHistory(Request $request)
    {
        [$from, $to] = $this->dateRange($request, 30);

        $query = $this->applyDateFilter(Invoice::with('customer', 'cashier'), 'invoice_date', $from, $to);
        if ($request->filled('customer_id')) $query->where('customer_id', $request->customer_id);
        $invoices = $query->orderByDesc('invoice_date')->get();

        $rows = $invoices->map(fn($i) => [
            'date'     => $i->invoice_date ? $i->invoice_date->format('d M Y') : '-',
            'customer' => $i->customer->name ?? 'Walk-in',
            'total'    => 'Rs. ' . number_format($i->total, 2),
            'paid'     => 'Rs. ' . number_format($i->paid_amount, 2),
            'due'      => 'Rs. ' . number_format($i->due_amount, 2),
        ])->all();

        return view('reports.list', [
            'title' => 'Customer Sale History',
            'columns' => ['date'=>'Date','customer'=>'Customer','total'=>'Total','paid'=>'Paid','due'=>'Due'],
            'rows' => $rows,
            'from' => $from, 'to' => $to,
        ]);
    }

    // ── Sale Summary ──────────────────────────────────────────────────
    public function saleSummary(Request $request)
    {
        [$from, $to] = $this->dateRange($request, 30);

        $invoices = $this->applyDateFilter(Invoice::query(), 'invoice_date', $from, $to)->get();

        $byDate = $invoices->groupBy(fn($i) => $i->invoice_date?->format('Y-m-d'));
        $rows = $byDate->map(function ($group, $date) {
            return [
                'date'   => \Carbon\Carbon::parse($date)->format('d M Y'),
                'bills'  => $group->count(),
                'total'  => 'Rs. ' . number_format($group->sum('total'), 2),
                'paid'   => 'Rs. ' . number_format($group->sum('paid_amount'), 2),
                'due'    => 'Rs. ' . number_format($group->sum('due_amount'), 2),
            ];
        })->sortByDesc('date')->values()->all();

        return view('reports.list', [
            'title' => 'Sale Summary',
            'columns' => ['date'=>'Date','bills'=>'Bills','total'=>'Total','paid'=>'Paid','due'=>'Due'],
            'rows' => $rows,
            'from' => $from, 'to' => $to,
            'summaryCards' => [
                ['label' => 'Total Bills', 'value' => $invoices->count()],
                ['label' => 'Total Sales', 'value' => 'Rs. '.number_format($invoices->sum('total'),2), 'color' => 'success'],
                ['label' => 'Total Paid', 'value' => 'Rs. '.number_format($invoices->sum('paid_amount'),2), 'color' => 'primary'],
                ['label' => 'Total Due', 'value' => 'Rs. '.number_format($invoices->sum('due_amount'),2), 'color' => 'danger'],
            ],
        ]);
    }

    // ── Sale Return History ──────────────────────────────────────────
    public function saleReturnHistory(Request $request)
    {
        [$from, $to] = $this->dateRange($request, 30);

        $returns = $this->applyDateFilter(\App\Models\CustomerReturn::with('customer', 'cashier'), 'created_at', $from, $to)
            ->orderByDesc('created_at')
            ->get();

        $rows = $returns->map(fn($r) => [
            'date'     => $r->created_at->format('d M Y'),
            'customer' => $r->customer->name ?? 'Walk-in',
            'cashier'  => $r->cashier->name ?? '-',
            'refund'   => 'Rs. ' . number_format($r->total_refund, 2),
            'reason'   => $r->reason ?? '-',
            'status'   => ucfirst($r->status),
        ])->all();

        return view('reports.list', [
            'title' => 'Sale Return History',
            'columns' => ['date'=>'Date','customer'=>'Customer','cashier'=>'Cashier','refund'=>'Refund Amount','reason'=>'Reason','status'=>'Status'],
            'rows' => $rows,
            'from' => $from, 'to' => $to,
        ]);
    }

    // ── Cash States (Register open/close log) ────────────────────────
    public function cashStates(Request $request)
    {
        $registers = \App\Models\CashRegister::with('user')->orderByDesc('opened_at')->get();

        $rows = $registers->map(fn($r) => [
            'user'    => $r->user->name ?? '-',
            'opened'  => $r->opened_at->format('d M Y H:i'),
            'closed'  => $r->closed_at?->format('d M Y H:i') ?? '-',
            'opening' => 'Rs. ' . number_format($r->opening_amount, 2),
            'expected'=> 'Rs. ' . number_format($r->expected_closing_amount, 2),
            'actual'  => 'Rs. ' . number_format($r->actual_closing_amount, 2),
            'diff'    => 'Rs. ' . number_format($r->difference_amount, 2),
            'status'  => $r->status === 'open' ? 'Open' : 'Closed',
        ])->all();

        return view('reports.list', [
            'title' => 'Cash Register States',
            'columns' => ['user'=>'User','opened'=>'Opened At','closed'=>'Closed At','opening'=>'Opening','expected'=>'Expected','actual'=>'Actual','diff'=>'Difference','status'=>'Status'],
            'rows' => $rows,
            'showDateFilter' => false,
        ]);
    }

    // ── Products Low Stock ────────────────────────────────────────────
    public function productsLowStock(Request $request)
    {
        $products = Product::with('category')
            ->whereColumn('stock', '<=', 'low_stock_threshold')
            ->orderBy('stock')
            ->get();

        $rows = $products->map(fn($p) => [
            'name'     => $p->name,
            'category' => $p->category->name ?? '-',
            'stock'    => $p->stock,
            'threshold'=> $p->low_stock_threshold,
            'status'   => $p->stock <= 0 ? 'Out of Stock' : 'Low Stock',
        ])->all();

        return view('reports.list', [
            'title' => 'Products Low Stock',
            'columns' => ['name'=>'Product','category'=>'Category','stock'=>'Current Stock','threshold'=>'Threshold','status'=>'Status'],
            'rows' => $rows,
            'showDateFilter' => false,
        ]);
    }
}
