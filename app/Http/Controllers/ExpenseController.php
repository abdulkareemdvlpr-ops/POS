<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with('user', 'supplier');

        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('expense_date', [$request->from_date, $request->to_date]);
        }

        $totalAmount = (clone $query)->sum('amount');
        $expenses   = $query->latest()->paginate(20);
        $categories = Expense::categories();

        return view('expenses.index', compact('expenses', 'totalAmount', 'categories'));
    }

    public function create()
    {
        $categories = Expense::categories();
        $companies  = Supplier::where('status', 1)->where('supplier_type', 'company')->orderBy('name')->get();
        return view('expenses.create', compact('categories', 'companies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'category'     => 'required|string|max:100',
            'supplier_id'  => [
                Rule::requiredIf($request->category === Expense::DIRECT_COMPANY_BILL),
                'nullable',
                Rule::exists('suppliers', 'id')->where('supplier_type', 'company'),
            ],
            'bill_number'  => 'nullable|string|max:100',
            'amount'       => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'notes'        => 'nullable|string|max:500',
        ]);

        Expense::create($request->only('title', 'category', 'supplier_id', 'bill_number', 'amount', 'expense_date', 'notes')
            + ['user_id' => auth()->id()]);

        return redirect()->route('expenses.index')->with('success', 'Expense recorded successfully!');
    }

    public function edit(Expense $expense)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $categories = Expense::categories();
        $companies  = Supplier::where('status', 1)->where('supplier_type', 'company')->orderBy('name')->get();
        return view('expenses.edit', compact('expense', 'categories', 'companies'));
    }

    public function update(Request $request, Expense $expense)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $request->validate([
            'title'        => 'required|string|max:255',
            'category'     => 'required|string|max:100',
            'supplier_id'  => [
                Rule::requiredIf($request->category === Expense::DIRECT_COMPANY_BILL),
                'nullable',
                Rule::exists('suppliers', 'id')->where('supplier_type', 'company'),
            ],
            'bill_number'  => 'nullable|string|max:100',
            'amount'       => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'notes'        => 'nullable|string|max:500',
        ]);

        $expense->update($request->only('title', 'category', 'supplier_id', 'bill_number', 'amount', 'expense_date', 'notes'));

        return redirect()->route('expenses.index')->with('success', 'Expense updated successfully!');
    }

    public function destroy(Expense $expense)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'Expense deleted.');
    }
}
