@extends('layouts.app')

@section('title', 'Supplier Ledger - ' . $supplier->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        @if(($supplier->supplier_type ?? 'distributor') === 'company')
            <h4 class="mb-0 fw-bold text-primary">Company Purchase Ledger (Direct Procurement)</h4>
            <h6 class="text-muted mb-0">{{ $supplier->company_name ?? $supplier->name }} <span class="badge bg-secondary font-weight-normal text-white">Direct Company</span></h6>
        @else
            <h4 class="mb-0 fw-bold text-success">Vendor Khata Ledger (Supplier Credit / Accounts Payable)</h4>
            <h6 class="text-muted mb-0">{{ $supplier->company_name ?? $supplier->name }} <span class="badge bg-info text-dark font-weight-normal">Local Distribution</span></h6>
        @endif
    </div>
    <div>
        <button class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#paymentModal">
            @if(($supplier->supplier_type ?? 'distributor') === 'company')
                <i class="bi bi-wallet2 me-1"></i>Record Payment
            @else
                <i class="bi bi-wallet2 me-1"></i>Pay Kisht / Installment
            @endif
        </button>
        <a href="{{ auth()->user()->isAdmin() ? route('suppliers.index') : route('purchases.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
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
                <h6 class="text-white-50 mb-1">Total Purchases</h6>
                <h4 class="mb-0 fw-bold">Rs. {{ number_format($totalPurchased ?? 0, 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-success text-white">
            <div class="card-body p-4">
                <h6 class="text-white-50 mb-1">Total Paid</h6>
                <h4 class="mb-0 fw-bold">Rs. {{ number_format($totalPaid ?? 0, 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-danger text-white">
            <div class="card-body p-4">
                <h6 class="text-white-50 mb-1">Outstanding Balance</h6>
                <h4 class="mb-0 fw-bold">Rs. {{ number_format($balance ?? 0, 2) }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3"><span class="fw-bold">Purchases History Log</span></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr class="bg-light">
                            <th class="ps-3">Date</th>
                            <th>Purchase ID</th>
                            <th>Total</th>
                            <th>Due</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($supplier->purchases as $p)
                        <tr>
                            <td class="ps-3">{{ $p->purchase_date->format('d M Y') }}</td>
                            <td><a href="{{ route('purchases.show', $p) }}">#{{ $p->id }}</a></td>
                            <td class="fw-bold">Rs. {{ number_format($p->total_amount, 2) }}</td>
                            <td class="text-danger">Rs. {{ number_format($p->due_amount, 2) }}</td>
                            <td>
                                @if($p->payment_status === 'paid')
                                    <span class="badge bg-success">Paid</span>
                                @elseif($p->payment_status === 'partial')
                                    <span class="badge bg-warning text-dark">Partial</span>
                                @else
                                    <span class="badge bg-danger">Unpaid</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No purchases logged.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3"><span class="fw-bold">Payments & Installment Log</span></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr class="bg-light">
                            <th class="ps-3">Date</th>
                            <th>Purchase #</th>
                            <th>Method</th>
                            <th>Slip #</th>
                            <th class="text-end pe-3">Paid Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($supplier->payments as $pmt)
                        <tr>
                            <td class="ps-3">{{ $pmt->payment_date->format('d M Y') }}</td>
                            <td>
                                @if($pmt->purchase_id)
                                    <a href="{{ route('purchases.show', $pmt->purchase_id) }}">#{{ $pmt->purchase_id }}</a>
                                @else
                                    &mdash;
                                @endif
                            </td>
                            <td class="text-capitalize">{{ $pmt->payment_method }}</td>
                            <td>{{ $pmt->slip_number ?? '—' }}</td>
                            <td class="text-end pe-3 text-success fw-bold">Rs. {{ number_format($pmt->amount, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No payments logged.</td>
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
        <form action="{{ route('supplier-payments.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">
                    @if(($supplier->supplier_type ?? 'distributor') === 'company')
                        <i class="bi bi-wallet2 me-2"></i>Record Company Payment
                    @else
                        <i class="bi bi-wallet2 me-2"></i>Record Installment (Kisht)
                    @endif
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="supplier_id" value="{{ $supplier->id }}">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Amount Paid <span class="text-danger">*</span></label>
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
                <button type="submit" class="btn btn-primary">Save Payment</button>
            </div>
        </form>
    </div>
</div>

@endsection
