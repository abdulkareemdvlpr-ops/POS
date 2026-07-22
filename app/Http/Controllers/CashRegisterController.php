<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\Invoice;
use App\Models\CustomerPayment;
use App\Models\SupplierPayment;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashRegisterController extends Controller
{
    public function index()
    {
        // Only admin can view logs
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $logs = CashRegister::with('user')->orderBy('id', 'desc')->paginate(20);
        return view('cash-registers.index', compact('logs'));
    }

    public function show(CashRegister $cashRegister)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $cashRegister->load('user');

        $periodStart = $cashRegister->opened_at;
        $periodEnd = $cashRegister->closed_at ?? now();

        $invoices = Invoice::with(['customer', 'cashier', 'items.product'])
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->orderBy('created_at')
            ->get();

        $collections = CustomerPayment::with('customer')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->orderBy('created_at')
            ->get();

        $payouts = SupplierPayment::with('supplier')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->orderBy('created_at')
            ->get();

        $expenses = Expense::whereBetween('created_at', [$periodStart, $periodEnd])
            ->orderBy('created_at')
            ->get();

        return view('cash-registers.show', compact(
            'cashRegister',
            'invoices',
            'collections',
            'payouts',
            'expenses'
        ));
    }

    public function open()
    {
        $active = CashRegister::active();
        if ($active) {
            return redirect()->route('cash-registers.status');
        }
        return view('cash-registers.open');
    }

    public function store(Request $request)
    {
        $request->validate([
            'opening_amount' => 'required|numeric|min:0',
            'notes'          => 'nullable|string|max:500',
        ]);

        $active = CashRegister::active();
        if ($active) {
            return redirect()->route('cash-registers.status');
        }

        CashRegister::create([
            'user_id'        => auth()->id(),
            'opened_at'      => now(),
            'opening_amount' => $request->opening_amount,
            'notes'          => $request->notes,
            'status'         => 'open',
        ]);

        return redirect()->route('dashboard')->with('success', 'Cash Register opened successfully. Start processing sales!');
    }

    public function status()
    {
        $active = CashRegister::active();
        if (!$active) {
            return redirect()->route('cash-registers.open');
        }

        $stats = $this->getRegisterStats($active);
        return view('cash-registers.status', compact('active', 'stats'));
    }

    public function close(Request $request)
    {
        $request->validate([
            'actual_closing_amount' => 'required|numeric|min:0',
            'closing_notes'         => 'nullable|string|max:500',
        ]);

        $active = CashRegister::active();
        if (!$active) {
            return redirect()->route('cash-registers.open');
        }

        $stats = $this->getRegisterStats($active);

        DB::transaction(function () use ($active, $stats, $request) {
            $actual = $request->actual_closing_amount;
            $expected = $stats['expected'];
            $difference = $actual - $expected;

            $active->update([
                'closed_at'               => now(),
                'cash_sales'              => $stats['cash_sales'],
                'customer_collections'    => $stats['customer_collections'],
                'supplier_payouts'        => $stats['supplier_payouts'],
                'expenses'                => $stats['expenses'],
                'expected_closing_amount' => $expected,
                'actual_closing_amount'   => $actual,
                'difference_amount'       => $difference,
                'status'                  => 'closed',
                'notes'                   => $request->closing_notes,
            ]);
        });

        return redirect()->route('dashboard')->with('success', 'Cash Register closed successfully. Shift reconciliation recorded!');
    }

    private function getRegisterStats($register)
    {
        $start = $register->opened_at;
        $end = now();

        $cashSales = Invoice::where('payment_method', 'cash')
            ->whereBetween('created_at', [$start, $end])
            ->sum('paid_amount');

        $customerCollections = CustomerPayment::where('payment_method', 'cash')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');

        $supplierPayouts = SupplierPayment::where('payment_method', 'cash')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');

        $expenses = Expense::whereBetween('created_at', [$start, $end])
            ->sum('amount');

        $expected = $register->opening_amount + $cashSales + $customerCollections - $supplierPayouts - $expenses;

        return [
            'cash_sales' => $cashSales,
            'customer_collections' => $customerCollections,
            'supplier_payouts' => $supplierPayouts,
            'expenses' => $expenses,
            'expected' => $expected
        ];
    }
}
