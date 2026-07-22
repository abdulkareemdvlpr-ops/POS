@extends('layouts.app')

@section('title', 'Purchase Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Purchase Order Details</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Purchases</a></li>
                <li class="breadcrumb-item active">#{{ $purchase->id }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white py-3"><span class="fw-bold">Summary</span></div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr><td class="text-muted">Supplier:</td><td class="fw-semibold">{{ $purchase->supplier->name ?? '—' }}</td></tr>
                    <tr><td class="text-muted">Date:</td><td>{{ $purchase->purchase_date->format('d M Y') }}</td></tr>
                    <tr><td class="text-muted">Payment Method:</td><td class="text-capitalize">{{ $purchase->payment_method }}</td></tr>
                    <tr><td class="text-muted">Status:</td><td>
                        @if($purchase->payment_status === 'paid')
                            <span class="badge bg-success">Paid</span>
                        @elseif($purchase->payment_status === 'partial')
                            <span class="badge bg-warning text-dark">Partial</span>
                        @else
                            <span class="badge bg-danger">Unpaid</span>
                        @endif
                    </td></tr>
                </table>
                <hr>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total Bill:</span>
                    <span class="fw-bold">Rs. {{ number_format($purchase->total_amount, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total Paid:</span>
                    <span class="fw-bold text-success">Rs. {{ number_format($purchase->paid_amount, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Due Amount:</span>
                    <span class="fw-bold text-danger">Rs. {{ number_format($purchase->due_amount, 2) }}</span>
                </div>
            </div>
        </div>

        @if($purchase->due_amount > 0)
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3"><span class="fw-bold">Pay Supplier Installment (Qist)</span></div>
            <div class="card-body">
                <form action="{{ route('supplier-payments.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="supplier_id" value="{{ $purchase->supplier_id }}">
                    <input type="hidden" name="purchase_id" value="{{ $purchase->id }}">
                    <div class="mb-3">
                        <label class="form-label">Payment Date</label>
                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="bank">Bank Deposit</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (Max: Rs. {{ number_format($purchase->due_amount, 2) }})</label>
                        <input type="number" step="0.01" name="amount" class="form-control" max="{{ $purchase->due_amount }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slip / Cheque Number</label>
                        <input type="text" name="slip_number" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100 py-2">Submit Payment</button>
                </form>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white py-3"><span class="fw-bold">Items List</span></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr class="bg-light">
                            <th class="ps-3">Medicine</th>
                            <th>Total Dabba</th>
                            <th>Cost / Box</th>
                            <th>Sale / Box</th>
                            <th>Batch / Expiry</th>
                            <th class="text-end pe-3">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchase->items as $item)
                        @php
                            $unitsPerBox = $item->product?->unitsPerBox() ?: 1;
                            $boxes = $unitsPerBox > 0 ? $item->qty / $unitsPerBox : $item->qty;
                        @endphp
                        <tr>
                            <td class="ps-3">
                                <div class="fw-semibold">{{ $item->product->name ?? '—' }}</div>
                            </td>
                            <td>{{ number_format($boxes, floor($boxes) == $boxes ? 0 : 2) }}</td>
                            <td>Rs. {{ number_format($item->cost_price * $unitsPerBox, 2) }}</td>
                            <td>Rs. {{ number_format($item->retail_price * $unitsPerBox, 2) }}</td>
                            <td>
                                <div>Batch: {{ $item->batch_number ?? '—' }}</div>
                                <div class="small text-muted">Exp: {{ $item->expiry_date ? $item->expiry_date->format('d M Y') : '—' }}</div>
                            </td>
                            <td class="text-end pe-3 fw-semibold">Rs. {{ number_format($item->total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white py-3"><span class="fw-bold">Payments History</span></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr class="bg-light">
                            <th class="ps-3">Date</th>
                            <th>Method</th>
                            <th>Slip #</th>
                            <th>Notes</th>
                            <th class="text-end pe-3">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchase->payments as $pmt)
                        <tr>
                            <td class="ps-3">{{ $pmt->payment_date->format('d M Y') }}</td>
                            <td class="text-capitalize">{{ $pmt->payment_method }}</td>
                            <td>{{ $pmt->slip_number ?? '—' }}</td>
                            <td>{{ $pmt->notes ?? '—' }}</td>
                            <td class="text-end pe-3 fw-semibold text-success">Rs. {{ number_format($pmt->amount, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No installment payments recorded.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
