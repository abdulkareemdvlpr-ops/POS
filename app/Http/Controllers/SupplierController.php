<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::latest()->paginate(15);
        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'company_name'    => 'nullable|string|max:255',
            'supplier_type'   => 'required|string|in:distributor,company',
            'email'           => 'nullable|email|unique:suppliers,email',
            'phone'           => 'required|string|max:20',
            'ntn'             => 'nullable|string|max:50',
            'city'            => 'nullable|string|max:100',
            'address'         => 'nullable|string|max:500',
            'notes'           => 'nullable|string|max:500',
            'opening_balance' => 'nullable|numeric|min:0',
            'status'          => 'nullable|boolean',
        ]);

        Supplier::create($request->only([
            'name','company_name','supplier_type','email','phone','ntn',
            'city','address','notes','opening_balance','status'
        ]));

        return redirect()->route('suppliers.index')->with('success', 'Supplier added successfully!');
    }

    public function show(Supplier $supplier)
    {
        $supplier->load('products.category');
        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'company_name'    => 'nullable|string|max:255',
            'supplier_type'   => 'required|string|in:distributor,company',
            'email'           => 'nullable|email|unique:suppliers,email,' . $supplier->id,
            'phone'           => 'required|string|max:20',
            'ntn'             => 'nullable|string|max:50',
            'city'            => 'nullable|string|max:100',
            'address'         => 'nullable|string|max:500',
            'notes'           => 'nullable|string|max:500',
            'opening_balance' => 'nullable|numeric|min:0',
            'status'          => 'nullable|boolean',
        ]);

        $supplier->update($request->only([
            'name','company_name','supplier_type','email','phone','ntn',
            'city','address','notes','opening_balance','status'
        ]));

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully!');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted successfully!');
    }
}
