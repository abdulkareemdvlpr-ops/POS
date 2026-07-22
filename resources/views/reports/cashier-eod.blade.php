@extends('layouts.app')

@section('title', 'My Daily Report')

@push('styles')
<style>
@media print {
    .no-print, .sidebar, .topbar { display: none !important; }
    .main-content { margin-left: 0 !important; }
    .page-content { padding: 0 !important; }
    body { background: #fff !important; }
}
.summary-card { border-radius: 10px; padding: 20px; text-align: center; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h4 class="mb-0 fw-bold">Daily Sales Report</h4>
        <p class="text-muted small mb-0">
            {{ $selectedCashier ? $selectedCashier->name : (auth()->user()->isAdmin() ? 'All Cashiers' : auth()->user()->name) }}
            &mdash; {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
        </p>
    </div>
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print">
        <i class="bi bi-printer me-1"></i>Print / Save
    </button>
</div>

{{-- Filters --}}
<div class="card mb-4 no-print">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small fw-semibold mb-1">Date</label>
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}">
            </div>
            @if(auth()->user()->isAdmin() && $cashiers->isNotEmpty())
            <div class="col-auto">
                <label class="form-label small fw-semibold mb-1">Cashier</label>
                <select name="cashier_id" class="form-select form-select-sm">
                    <option value="">All Cashiers</option>
                    @foreach($cashiers as $c)
                    <option value="{{ $c->id }}" {{ $cashierId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">View</button>
            </div>
        </form>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="summary-card" style="background:#dbeafe;">
            <div class="fs-3 fw-bold text-primary">{{ $summary['count'] }}</div>
            <div class="text-muted small">Bills Issued</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="summary-card" style="background:#d1fae5;">
            <div class="fs-4 fw-bold text-success">Rs. {{ number_format($summary['total_paid'], 0) }}</div>
            <div class="text-muted small">Total Collected</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="summary-card" style="background:#e0f2fe;">
            <div class="fw-bold" style="font-size:1.1rem;">
                Cash: Rs. {{ number_format($summary['cash'], 0) }}<br>
                Card: Rs. {{ number_format($summary['card'], 0) }}
            </div>
            <div class="text-muted small">Payment Breakdown</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="summary-card" style="background:#fef3c7;">
            <div class="fs-4 fw-bold text-warning">Rs. {{ number_format($summary['credit'], 0) }}</div>
            <div class="text-muted small">Pending / Credit</div>
        </div>
    </div>
</div>

{{-- Cash Drawer Reconciliation --}}
<div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #f8fafc;">
    <div class="card-header bg-transparent border-0 pt-4 pb-0 text-white">
        <h5 class="fw-bold mb-0"><i class="bi bi-wallet2 me-2"></i>Cash Drawer Reconciliation (EOD)</h5>
        <span class="text-white-50 small">Live Till Register Reconciliation for {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</span>
    </div>
    <div class="card-body py-4">
        <div class="row g-4 align-items-center">
            <div class="col-md-8">
                <div style="font-size: 0.95rem;">
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
                        <span class="text-white-50"><i class="bi bi-plus-circle-fill text-success me-2"></i>Cash Sales (Counter Sales)</span>
                        <span class="fw-semibold">Rs. {{ number_format($summary['cash'], 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
                        <span class="text-white-50"><i class="bi bi-plus-circle-fill text-success me-2"></i>Customer Collections (Udhaar Cash-In)</span>
                        <span class="fw-semibold">Rs. {{ number_format($summary['customer_collections'], 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
                        <span class="text-white-50"><i class="bi bi-dash-circle-fill text-danger me-2"></i>Supplier Payouts (Counter Kisht)</span>
                        <span class="fw-semibold text-danger">- Rs. {{ number_format($summary['supplier_payouts'], 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-3">
                        <span class="text-white fw-bold fs-5">Net Drawer Cash (Live Cash in Hand)</span>
                        <span class="fw-bold text-success fs-5">Rs. {{ number_format($summary['net_drawer_cash'], 2) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center border-start border-secondary">
                <div class="p-3">
                    <h6 class="text-white-50 mb-1 text-uppercase tracking-wider" style="font-size: 0.75rem; letter-spacing: 1px;">Live Till Cash</h6>
                    <h2 class="fw-extrabold text-success mb-0">Rs. {{ number_format($summary['net_drawer_cash'], 0) }}</h2>
                    <small class="text-white-50 d-block mt-2">Reconcile this amount with physical cash in the drawer at shift end.</small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Invoices Table --}}
<div class="card">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <span class="fw-bold">Invoice Details — {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</span>
        <span class="badge bg-primary">{{ $invoices->count() }} bills</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Customer</th>
                        @if(auth()->user()->isAdmin())<th>Cashier</th>@endif
                        <th>Time</th>
                        <th>Payment</th>
                        <th class="text-end">Amount</th>
                        <th class="pe-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                    <tr>
                        <td class="ps-3">
                            <a href="{{ route('invoices.show', $inv) }}" class="fw-semibold text-decoration-none">#{{ str_pad($inv->id, 4, '0', STR_PAD_LEFT) }}</a>
                        </td>
                        <td>{{ $inv->customer->name ?? 'Walk-in' }}</td>
                        @if(auth()->user()->isAdmin())
                        <td class="text-muted small">{{ $inv->cashier->name ?? '—' }}</td>
                        @endif
                        <td class="text-muted small">{{ $inv->created_at->format('h:i A') }}</td>
                        <td class="text-muted small">{{ ucfirst(str_replace('_',' ',$inv->payment_method ?? 'cash')) }}</td>
                        <td class="text-end fw-semibold">Rs. {{ number_format($inv->total, 2) }}</td>
                        <td class="pe-3">
                            <span class="badge rounded-pill {{ $inv->status==='paid' ? 'badge-active' : 'badge-pending' }}">
                                {{ ucfirst($inv->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">No bills found for this date</td></tr>
                    @endforelse
                </tbody>
                @if($invoices->isNotEmpty())
                <tfoot>
                    <tr class="table-dark">
                        <td colspan="{{ auth()->user()->isAdmin() ? 5 : 4 }}" class="ps-3 fw-bold">TOTAL</td>
                        <td class="text-end fw-bold">Rs. {{ number_format($summary['total_paid'], 2) }}</td>
                        <td class="pe-3"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

{{-- Print footer --}}
<div class="d-none d-print-block mt-4 text-center text-muted small">
    Report generated on {{ now()->format('d M Y h:i A') }} |
    {{ $selectedCashier ? $selectedCashier->name : auth()->user()->name }}
</div>
@endsection
