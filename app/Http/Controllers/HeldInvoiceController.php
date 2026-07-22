<?php

namespace App\Http\Controllers;

use App\Models\HeldInvoice;
use Illuminate\Http\Request;

class HeldInvoiceController extends Controller
{
    public function index()
    {
        $heldInvoices = HeldInvoice::with('customer', 'cashier')
            ->where('cashier_id', auth()->id())
            ->latest()
            ->get();
        return view('held-invoices.index', compact('heldInvoices'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items'   => 'required|array|min:1',
            'label'   => 'nullable|string|max:100',
        ]);

        try {
            HeldInvoice::create([
                'cashier_id'  => auth()->id(),
                'customer_id' => $request->customer_id ?: null,
                'label'       => $request->label ?: ('Hold #' . now()->format('H:i:s')),
                'data'        => [
                    'items'          => $request->items,
                    'discount'       => $request->discount ?? 0,
                    'discount_type'  => $request->discount_type ?? 'flat',
                    'tax'            => $request->tax ?? 0,
                    'service_fee'    => $request->service_fee ?? 0,
                    'payment_method' => $request->payment_method ?? 'cash',
                    'notes'          => $request->notes ?? '',
                    'customer_id'    => $request->customer_id ?? '',
                ],
            ]);

            // JSON request (old fetch-based) → JSON response
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Bill held successfully.']);
            }

            // Form POST (new approach) → redirect with success message
            return redirect()->route('invoices.create')
                ->with('success', '✅ Bill hold ho gaya! Resume Bill se wapas la saktay hain.');

        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
            }
            return redirect()->route('invoices.create')
                ->with('error', '❌ Hold Bill Error: ' . $e->getMessage());
        }
    }

    public function show(HeldInvoice $heldInvoice)
    {
        // Admins can view any held bill; cashiers only their own
        if (!auth()->user()->isAdmin() && $heldInvoice->cashier_id !== auth()->id()) {
            abort(403, 'You do not own this held bill.');
        }
        return response()->json($heldInvoice->data + ['label' => $heldInvoice->label, 'customer_id' => $heldInvoice->customer_id]);
    }

    public function destroy(HeldInvoice $heldInvoice)
    {
        if (!auth()->user()->isAdmin() && $heldInvoice->cashier_id !== auth()->id()) {
            abort(403, 'You do not own this held bill.');
        }
        $heldInvoice->delete();
        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('invoices.create')->with('success', 'Held bill removed.');
    }
}
