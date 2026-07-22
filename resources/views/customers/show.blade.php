@extends('layouts.app')

@section('title', 'Customer Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Customer Details</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                <li class="breadcrumb-item active">{{ $customer->name }}</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('customers.ledger', $customer) }}" class="btn btn-primary">
            <i class="bi bi-journal-text me-2"></i>View Ledger
        </a>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-warning">
            <i class="bi bi-pencil me-2"></i>Edit
        </a>
        @endif
        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center py-4">
                <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                    style="width:80px;height:80px;background:#dbeafe;color:#1d4ed8;font-weight:700;font-size:2rem;">
                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                </div>
                <h5 class="fw-bold mb-1">{{ $customer->name }}</h5>
                <p class="text-muted small mb-3">{{ $customer->email ?? 'No email' }}</p>
                @if($customer->status ?? true)
                    <span class="badge rounded-pill badge-active">Active</span>
                @else
                    <span class="badge rounded-pill badge-inactive">Inactive</span>
                @endif
            </div>
            <div class="card-footer">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-muted">Phone</td>
                        <td class="fw-semibold">{{ $customer->phone ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">CNIC</td>
                        <td class="fw-semibold">{{ $customer->cnic ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">City</td>
                        <td class="fw-semibold">{{ $customer->city ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Address</td>
                        <td class="small">{{ $customer->address ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Member Since</td>
                        <td class="small">{{ $customer->created_at->format('d M Y') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="card text-center py-3">
                    <div class="fs-3 fw-bold text-primary">{{ $customer->invoices->count() }}</div>
                    <div class="text-muted small">Total Orders</div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card text-center py-3">
                    <div class="fs-3 fw-bold text-success">Rs. {{ number_format($customer->invoices->sum('total'), 2) }}</div>
                    <div class="text-muted small">Total Spent</div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card text-center py-3">
                    <div class="fs-3 fw-bold text-warning">{{ $customer->invoices->where('status', '!=', 'paid')->count() }}</div>
                    <div class="text-muted small">Pending Bills</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header py-3"><i class="bi bi-receipt me-2"></i>Purchase History</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Invoice #</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customer->invoices as $invoice)
                            <tr>
                                <td class="ps-3 fw-semibold">#{{ $invoice->id }}</td>
                                <td class="text-muted small">{{ $invoice->created_at->format('d M Y') }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $invoice->items->count() }}</span></td>
                                <td class="fw-semibold">Rs. {{ number_format($invoice->total, 2) }}</td>
                                <td>
                                    <span class="badge rounded-pill {{ $invoice->status === 'paid' ? 'badge-active' : 'badge-inactive' }}">
                                        {{ ucfirst($invoice->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-outline-info">
                                        View
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No purchase history</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
