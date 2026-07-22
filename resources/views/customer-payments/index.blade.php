@extends('layouts.app')

@section('title', 'Customer Ledger & Collection')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Customer Payments & Collection Ledger</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Customer Payments</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('customers.create') }}" class="btn btn-primary">
        <i class="bi bi-person-plus me-2"></i>Add Customer
    </a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3"><span class="fw-bold">Record Customer Payment Collection (Udhaar Settle)</span></div>
            <div class="card-body">
                <form action="{{ route('customer-payments.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">Select Customer</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">
                                    {{ $customer->name }} (Credit Due: Rs. {{ number_format($customer->totalDue(), 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Collection Date <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount Collected <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" step="0.01" name="amount" class="form-control" min="0.01" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slip Number / Reference</label>
                        <input type="text" name="slip_number" class="form-control" placeholder="Slip #">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Remarks..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2">Save Receipt & Update Credit</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold">Collection History Logs</span>
                <form method="GET" class="d-flex gap-2">
                    <select name="customer_id" class="form-select form-select-sm" style="width: 200px;" onchange="this.form.submit()">
                        <option value="">All Customers</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Slip #</th>
                                <th>Notes</th>
                                <th class="text-end pe-3">Collected Amount</th>
                                @if(auth()->user()->isAdmin())
                                <th class="text-end pe-3">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $pmt)
                            <tr>
                                <td class="ps-3">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $pmt->customer->name ?? '—' }}</div>
                                </td>
                                <td>{{ $pmt->payment_date->format('d M Y') }}</td>
                                <td class="text-capitalize">{{ $pmt->payment_method }}</td>
                                <td>{{ $pmt->slip_number ?? '—' }}</td>
                                <td>{{ $pmt->notes ?? '—' }}</td>
                                <td class="text-end pe-3 text-success fw-bold">Rs. {{ number_format($pmt->amount, 2) }}</td>
                                @if(auth()->user()->isAdmin())
                                <td class="text-end">
                                    <form action="{{ route('customer-payments.destroy', $pmt) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this collection entry?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Collection"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">No collection logs found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($payments->hasPages())
            <div class="card-footer bg-white">
                {{ $payments->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
