@extends('layouts.app')

@section('title', 'Profit / Loss Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Profit / Loss Report</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Profit / Loss</li>
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
    <div class="col-md-3 col-sm-6">
        <div class="card text-center border-primary">
            <div class="card-body py-3">
                <div class="text-muted small">Total Revenue</div>
                <div class="fs-5 fw-bold text-primary">Rs. {{ number_format($totals['revenue'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card text-center border-warning">
            <div class="card-body py-3">
                <div class="text-muted small">Total COGS</div>
                <div class="fs-5 fw-bold text-warning">Rs. {{ number_format($totals['cost'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card text-center {{ $totals['gross'] >= 0 ? 'border-success' : 'border-danger' }}">
            <div class="card-body py-3">
                <div class="text-muted small">Gross Profit</div>
                <div class="fs-5 fw-bold {{ $totals['gross'] >= 0 ? 'text-success' : 'text-danger' }}">
                    Rs. {{ number_format($totals['gross'], 2) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card text-center {{ $totals['net'] >= 0 ? 'border-success' : 'border-danger' }}">
            <div class="card-body py-3">
                <div class="text-muted small">Net Profit (after expenses)</div>
                <div class="fs-5 fw-bold {{ $totals['net'] >= 0 ? 'text-success' : 'text-danger' }}">
                    Rs. {{ number_format($totals['net'], 2) }}
                </div>
                <div class="text-muted" style="font-size:0.75rem;">Expenses: Rs. {{ number_format($totals['expenses'], 2) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- 30-Day Chart --}}
<div class="card mb-4 no-print">
    <div class="card-header py-3"><i class="bi bi-graph-up-arrow me-2 text-success"></i>Daily Revenue vs Cost — Last 30 Days</div>
    <div class="card-body">
        <canvas id="plChart" height="80"></canvas>
    </div>
</div>

{{-- Item-wise Margin --}}
<div class="card">
    <div class="card-header py-3"><i class="bi bi-table me-2"></i>Item-wise Margin — {{ $from }} to {{ $to }}</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Medicine</th>
                        <th>Category</th>
                        <th class="text-center">Qty Sold</th>
                        <th class="text-end">Revenue</th>
                        <th class="text-end">COGS</th>
                        <th class="text-end">Profit</th>
                        <th class="text-center">Margin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                    <tr>
                        <td class="ps-3 fw-semibold">{{ $item->product->name ?? '—' }}</td>
                        <td class="text-muted small">{{ $item->product->category->name ?? '—' }}</td>
                        <td class="text-center">{{ $item->total_qty }}</td>
                        <td class="text-end">Rs. {{ number_format($item->total_revenue, 2) }}</td>
                        <td class="text-end text-muted">Rs. {{ number_format($item->cost, 2) }}</td>
                        <td class="text-end fw-semibold {{ $item->profit >= 0 ? 'text-success' : 'text-danger' }}">
                            Rs. {{ number_format($item->profit, 2) }}
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $item->margin >= 20 ? 'bg-success' : ($item->margin >= 10 ? 'bg-warning text-dark' : 'bg-danger') }}">
                                {{ $item->margin }}%
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No sales data in this period</td></tr>
                    @endforelse
                </tbody>
                @if($items->isNotEmpty())
                <tfoot>
                    <tr class="table-dark">
                        <th class="ps-3" colspan="3">Totals</th>
                        <th class="text-end">Rs. {{ number_format($totals['revenue'], 2) }}</th>
                        <th class="text-end">Rs. {{ number_format($totals['cost'], 2) }}</th>
                        <th class="text-end">Rs. {{ number_format($totals['gross'], 2) }}</th>
                        <th></th>
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

@push('scripts')
<script src="{{ asset('offline/offline-chart.js') }}"></script>
<script>
const chartLabels  = {!! json_encode(array_column($dailyChart, 'date')) !!};
const chartRevenue = {!! json_encode(array_column($dailyChart, 'revenue')) !!};
const chartCost    = {!! json_encode(array_column($dailyChart, 'cost')) !!};
const chartProfit  = {!! json_encode(array_column($dailyChart, 'profit')) !!};

const ctx = document.getElementById('plChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: chartLabels,
        datasets: [
            { label: 'Revenue', data: chartRevenue, backgroundColor: 'rgba(14,165,233,0.6)' },
            { label: 'COGS',    data: chartCost,    backgroundColor: 'rgba(251,191,36,0.6)' },
            { label: 'Profit',  data: chartProfit,  backgroundColor: 'rgba(34,197,94,0.6)',  type: 'line', borderColor: '#22c55e', fill: false },
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'top' } } }
});
</script>
@endpush
