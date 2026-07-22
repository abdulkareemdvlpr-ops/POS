@extends('layouts.app')

@section('title', 'Customer Ledger - ' . $customer->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Customer Account Ledger</h4>
        <h6 class="text-muted mb-0">{{ $customer->name }} ({{ $customer->phone ?? 'No Phone' }})</h6>
    </div>
    <div>
        <button class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#paymentModal">
            <i class="bi bi-wallet2 me-1"></i>Receive Kisht
        </button>
        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm">Back to Customers</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-secondary text-white">
            <div class="card-body p-4">
                <h6 class="text-white-50 mb-1">Opening Balance</h6>
                <h4 class="mb-0 fw-bold">Rs. {{ number_format($openingBalance ?? 0, 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body p-4">
                <h6 class="text-white-50 mb-1">Total Sales Bill</h6>
                <h4 class="mb-0 fw-bold">Rs. {{ number_format($totalSales ?? 0, 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-success text-white">
            <div class="card-body p-4">
                <h6 class="text-white-50 mb-1">Total Received</h6>
                <h4 class="mb-0 fw-bold">Rs. {{ number_format($totalPaid ?? 0, 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-danger text-white">
            <div class="card-body p-4">
                <h6 class="text-white-50 mb-1">Outstanding Balance (Udhaar)</h6>
                <h4 class="mb-0 fw-bold">Rs. {{ number_format($balance ?? 0, 2) }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3"><span class="fw-bold">Bills & Invoices Ledger</span></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr class="bg-light">
                            <th class="ps-3">Date</th>
                            <th>Invoice ID</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customer->invoices as $inv)
                        <tr>
                            <td class="ps-3">{{ $inv->created_at->format('d M Y') }}</td>
                            <td>#{{ $inv->id }}</td>
                            <td class="fw-bold">Rs. {{ number_format($inv->total, 2) }}</td>
                            <td class="text-success">Rs. {{ number_format($inv->paid_amount, 2) }}</td>
                            <td class="text-danger">Rs. {{ number_format($inv->due_amount, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No sales invoices logged.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3"><span class="fw-bold">Cash Collection Receipt Logs</span></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr class="bg-light">
                            <th class="ps-3">Date</th>
                            <th>Method</th>
                            <th>Slip #</th>
                            <th class="text-end pe-3">Collected Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customer->payments as $pmt)
                        <tr>
                            <td class="ps-3">{{ $pmt->payment_date->format('d M Y') }}</td>
                            <td class="text-capitalize">{{ $pmt->payment_method }}</td>
                            <td>{{ $pmt->slip_number ?? '—' }}</td>
                            <td class="text-end pe-3 text-success fw-bold">Rs. {{ number_format($pmt->amount, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">No collection records found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Payment Modal --}}
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('customer-payments.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-wallet2 me-2"></i>Receive Installment (Kisht)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Amount Received <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rs.</span>
                        <input type="number" step="0.01" class="form-control" name="amount" required max="{{ $balance > 0 ? $balance : '' }}">
                    </div>
                    <small class="text-muted">Total Outstanding: Rs. {{ number_format($balance, 2) }}</small>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="payment_date" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Slip / Reference #</label>
                    <input type="text" class="form-control" name="slip_number" placeholder="Optional">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Optional details..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Receipt</button>
            </div>
        </form>
    </div>
</div>

@endsection
