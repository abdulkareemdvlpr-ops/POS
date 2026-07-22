@extends('layouts.app')

@section('title', 'End of Day Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">End of Day Reconciliation</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">EOD Report</li>
            </ol>
        </nav>
    </div>
    <button onclick="window.print()" class="btn btn-outline-secondary no-print"><i class="bi bi-printer me-2"></i>Print</button>
</div>

{{-- Date Picker --}}
<div class="card mb-4 no-print">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Report Date</label>
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">Generate</button>
            </div>
        </form>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small mb-1"><i class="bi bi-cash me-1"></i>Cash Sales</div>
                <div class="fs-5 fw-bold text-success">Rs. {{ number_format($summary['cash'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small mb-1"><i class="bi bi-credit-card me-1"></i>Card Sales</div>
                <div class="fs-5 fw-bold text-primary">Rs. {{ number_format($summary['card'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small mb-1"><i class="bi bi-bank me-1"></i>Bank Transfer</div>
                <div class="fs-5 fw-bold text-info">Rs. {{ number_format($summary['bank_transfer'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small mb-1"><i class="bi bi-hourglass me-1"></i>Credit / Udhar</div>
                <div class="fs-5 fw-bold text-warning">Rs. {{ number_format($summary['credit'], 2) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Totals --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-success">
            <div class="card-body py-3 text-center">
                <div class="text-muted small">Total Collected (Paid)</div>
                <div class="fs-4 fw-bold text-success">Rs. {{ number_format($summary['total_paid'], 2) }}</div>
                <div class="text-muted small">{{ $summary['count'] }} invoice(s)</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-danger">
            <div class="card-body py-3 text-center">
                <div class="text-muted small">Total Expenses</div>
                <div class="fs-4 fw-bold text-danger">Rs. {{ number_format($expenses, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-primary">
            <div class="card-body py-3 text-center">
                <div class="text-muted small">Net Cash in Hand</div>
                <div class="fs-4 fw-bold text-primary">Rs. {{ number_format($netCash, 2) }}</div>
                <div class="text-muted small">Cash + Card + Bank − Expenses</div>
            </div>
        </div>
    </div>
</div>

{{-- Invoice List --}}
<div class="card">
    <div class="card-header py-3"><i class="bi bi-list-ul me-2"></i>Invoices — {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Invoice #</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                    <tr>
                        <td class="ps-3">
                            <a href="{{ route('invoices.show', $inv) }}" class="text-decoration-none">#{{ str_pad($inv->id, 4, '0', STR_PAD_LEFT) }}</a>
                        </td>
                        <td>{{ $inv->customer->name ?? 'Walk-in' }}</td>
                        <td>{{ $inv->items->count() }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $inv->payment_method)) }}</td>
                        <td>
                            <span class="badge {{ $inv->status==='paid'?'badge-active':'badge-pending' }}">{{ ucfirst($inv->status) }}</span>
                        </td>
                        <td class="text-end pe-3 fw-semibold">Rs. {{ number_format($inv->total, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No invoices for this date</td></tr>
                    @endforelse
                </tbody>
                @if($invoices->isNotEmpty())
                <tfoot>
                    <tr class="table-dark">
                        <th class="ps-3" colspan="5">Total</th>
                        <th class="text-end pe-3">Rs. {{ number_format($summary['total'], 2) }}</th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

{{-- Cash Register Shifts --}}
<div class="card mt-4">
    <div class="card-header py-3"><i class="bi bi-wallet2 me-2"></i>Cash Register Shifts — {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Shift ID</th>
                        <th>Cashier</th>
                        <th>Opened At</th>
                        <th>Closed At</th>
                        <th class="text-end">Opening Float</th>
                        <th class="text-end">Expected Drawer</th>
                        <th class="text-end">Actual Closed</th>
                        <th class="text-end">Difference</th>
                        <th class="pe-3">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($registers as $reg)
                    <tr>
                        <td class="ps-3">#{{ $reg->id }}</td>
                        <td class="fw-semibold">{{ $reg->user->name }}</td>
                        <td>{{ $reg->opened_at->format('h:i A') }}</td>
                        <td>{{ $reg->closed_at ? $reg->closed_at->format('h:i A') : 'Active' }}</td>
                        <td class="text-end">Rs. {{ number_format($reg->opening_amount, 2) }}</td>
                        <td class="text-end">Rs. {{ number_format($reg->expected_closing_amount, 2) }}</td>
                        <td class="text-end">Rs. {{ number_format($reg->actual_closing_amount, 2) }}</td>
                        <td class="text-end fw-bold">
                            @if($reg->status === 'open')
                                <span class="text-warning">Active Shift</span>
                            @elseif($reg->difference_amount == 0)
                                <span class="text-success">Balanced</span>
                            @elseif($reg->difference_amount > 0)
                                <span class="text-success">+{{ number_format($reg->difference_amount, 2) }} (Surplus)</span>
                            @else
                                <span class="text-danger">{{ number_format($reg->difference_amount, 2) }} (Deficit)</span>
                            @endif
                        </td>
                        <td class="pe-3 small text-muted text-wrap" style="max-width: 150px;">{{ $reg->notes ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No register shifts logged on this date</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
@media print {
    .no-print, .sidebar, .topbar { display: none !important; }
    .main-content { margin-left: 0 !important; }
    .page-content { padding: 0 !important; }
}
</style>
@endpush
