@extends('layouts.app')

@section('title', 'Tax / GST Audit Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Tax / GST Audit Report</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Tax Report</li>
            </ol>
        </nav>
    </div>
    <button onclick="window.print()" class="btn btn-outline-secondary no-print"><i class="bi bi-printer me-2"></i>Print</button>
</div>

{{-- Date Range --}}
<div class="card mb-4 no-print">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">From Date</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $from }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">To Date</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $to }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">Generate</button>
            </div>
        </form>
    </div>
</div>

{{-- Summary --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Taxable Sales</div>
                <div class="fs-5 fw-bold">Rs. {{ number_format($totals['taxable'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center border-warning">
            <div class="card-body py-3">
                <div class="text-muted small">Total Tax Collected</div>
                <div class="fs-5 fw-bold text-warning">Rs. {{ number_format($totals['tax_amount'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center border-primary">
            <div class="card-body py-3">
                <div class="text-muted small">Total Revenue (with Tax)</div>
                <div class="fs-5 fw-bold text-primary">Rs. {{ number_format($totals['total'], 2) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Invoice-wise Tax Table --}}
<div class="card">
    <div class="card-header py-3">
        <i class="bi bi-file-earmark-text me-2"></i>Invoice-wise Tax Detail
        <span class="text-muted ms-2 small">({{ $from }} to {{ $to }})</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Invoice #</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">Discount</th>
                        <th class="text-end">Taxable Amt</th>
                        <th class="text-center">Tax %</th>
                        <th class="text-end">Tax Amt</th>
                        <th class="text-end pe-3">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                    @php
                        $discAmt = $inv->discount_type === 'percent'
                            ? ($inv->subtotal * $inv->discount / 100)
                            : $inv->discount;
                    @endphp
                    <tr>
                        <td class="ps-3">
                            <a href="{{ route('invoices.show', $inv) }}" class="text-decoration-none">#{{ str_pad($inv->id, 4, '0', STR_PAD_LEFT) }}</a>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($inv->invoice_date)->format('d M Y') }}</td>
                        <td>{{ $inv->customer->name ?? 'Walk-in' }}</td>
                        <td class="text-end">{{ number_format($inv->subtotal, 2) }}</td>
                        <td class="text-end">{{ number_format($discAmt, 2) }}</td>
                        <td class="text-end fw-semibold">{{ number_format($inv->taxable_amount, 2) }}</td>
                        <td class="text-center">{{ $inv->tax }}%</td>
                        <td class="text-end text-warning fw-semibold">{{ number_format($inv->tax_amount, 2) }}</td>
                        <td class="text-end pe-3 fw-bold">{{ number_format($inv->total, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No taxable invoices in this period</td></tr>
                    @endforelse
                </tbody>
                @if($invoices->isNotEmpty())
                <tfoot>
                    <tr class="table-dark">
                        <th class="ps-3" colspan="5">Totals</th>
                        <th class="text-end">{{ number_format($totals['taxable'], 2) }}</th>
                        <th></th>
                        <th class="text-end">{{ number_format($totals['tax_amount'], 2) }}</th>
                        <th class="text-end pe-3">{{ number_format($totals['total'], 2) }}</th>
                    </tr>
                </tfoot>
                @endif
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
