@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>
.stat-card { border-radius: 12px; overflow: hidden; }
.stat-card .card-body { padding: 20px; }
.stat-icon { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.4rem; }
.expiry-expired { background: #fee2e2; border-left: 4px solid #dc2626; }
.expiry-warning { background: #fef3c7; border-left: 4px solid #f59e0b; }

/* Cashier analytics table */
.cashier-rank { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; flex-shrink: 0; }
.rank-1 { background: #fef3c7; color: #92400e; }
.rank-2 { background: #e2e8f0; color: #475569; }
.rank-3 { background: #fde8d8; color: #9a3412; }
.rank-n { background: #f1f5f9; color: #64748b; }

/* Chart container responsive */
.chart-wrapper { position: relative; width: 100%; }
.chart-wrapper canvas { width: 100% !important; display: block; }
</style>
@endpush

@section('content')

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#dbeafe;color:#1d4ed8;">
                    <i class="bi bi-receipt"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:.78rem;">Total Invoices</div>
                    <div class="fs-5 fw-bold">{{ number_format($totalInvoices ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#d1fae5;color:#065f46;">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:.78rem;">Total Revenue</div>
                    <div class="fs-5 fw-bold">Rs. {{ number_format($totalRevenue ?? 0, 0) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#fef3c7;color:#92400e;">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:.78rem;">Medicines</div>
                    <div class="fs-5 fw-bold">{{ number_format($totalProducts ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#fce7f3;color:#9d174d;">
                    <i class="bi bi-people"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:.78rem;">Customers</div>
                    <div class="fs-5 fw-bold">{{ number_format($totalCustomers ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Expiry Banners --}}
@if(($expiredProducts ?? collect())->isNotEmpty())
<div class="alert alert-danger d-flex align-items-center gap-2 mb-3 expiry-expired">
    <i class="bi bi-exclamation-octagon-fill fs-5 flex-shrink-0"></i>
    <div>
        <strong>{{ $expiredProducts->count() }} expired medicine(s) locked from billing.</strong>
        <a href="{{ route('stock-returns.index') }}" class="alert-link ms-2">Move to Returns</a>
    </div>
</div>
@endif
@if(($expiringProducts ?? collect())->isNotEmpty())
<div class="alert alert-warning d-flex align-items-center gap-2 mb-3 expiry-warning">
    <i class="bi bi-clock-history fs-5 flex-shrink-0"></i>
    <div>
        <strong>{{ $expiringProducts->count() }} medicine(s) expiring within 60 days (2 Months).</strong>
        <button class="btn btn-sm btn-warning ms-2 py-0" data-bs-toggle="modal" data-bs-target="#expiryModal">View List</button>
    </div>
</div>
@endif

{{-- Sales Chart + Pending --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold">Sales — Last 7 Days</span>
                <small class="text-muted">Daily Revenue (Rs.)</small>
            </div>
            <div class="card-body">
                <div class="chart-wrapper">
                    <canvas id="salesChart" height="160"
                        data-chart-labels="{{ json_encode(array_column($salesChart, 'date')) }}"
                        data-chart-values="{{ json_encode(array_column($salesChart, 'revenue')) }}">
                    </canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold">Pending / Udhar</span>
                <span class="badge bg-warning text-dark">Rs. {{ number_format($totalPending ?? 0, 0) }}</span>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($pendingInvoices ?? [] as $inv)
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <div>
                            <div class="fw-semibold small">#{{ $inv->id }} — {{ $inv->customer->name ?? 'Walk-in' }}</div>
                            <div class="text-muted" style="font-size:.73rem;">{{ $inv->created_at->format('d M Y') }}</div>
                        </div>
                        <span class="badge badge-pending rounded-pill">Rs. {{ number_format($inv->due_amount, 0) }}</span>
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted py-4">
                        <i class="bi bi-check-circle text-success fs-4 d-block mb-1"></i>No pending payments
                    </li>
                    @endforelse
                </ul>
            </div>
            @if(($pendingInvoices ?? collect())->isNotEmpty())
            <div class="card-footer text-center py-2">
                <a href="{{ route('invoices.index') }}" class="small text-decoration-none">View all invoices</a>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Cashier-wise Analytics (Admin Only) --}}
@if(auth()->user()->isAdmin() && ($cashierSales ?? collect())->isNotEmpty())
<div class="card mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <span class="fw-bold">Cashier Performance</span>
        <small class="text-muted">Today / This Month / All Time</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="ps-3" style="width:44px;">#</th>
                        <th>Cashier</th>
                        <th class="text-center">Today Bills</th>
                        <th class="text-end">Today Revenue</th>
                        <th class="text-center">Month Bills</th>
                        <th class="text-end">Month Revenue</th>
                        <th class="text-center">All Bills</th>
                        <th class="text-end pe-3">Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cashierSales as $i => $c)
                    <tr>
                        <td class="ps-3">
                            <div class="cashier-rank {{ $i===0?'rank-1':($i===1?'rank-2':($i===2?'rank-3':'rank-n')) }}">
                                {{ $i===0?'★':($i+1) }}
                            </div>
                        </td>
                        <td class="fw-semibold">{{ $c->name }}</td>
                        <td class="text-center">{{ $c->today_invoices }}</td>
                        <td class="text-end {{ $c->today_revenue>0?'text-success fw-semibold':'' }}">
                            Rs. {{ number_format($c->today_revenue, 0) }}
                        </td>
                        <td class="text-center">{{ $c->month_invoices }}</td>
                        <td class="text-end">Rs. {{ number_format($c->month_revenue, 0) }}</td>
                        <td class="text-center">{{ $c->total_invoices }}</td>
                        <td class="text-end pe-3 fw-bold">Rs. {{ number_format($c->total_revenue, 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Top Products + Low Stock + Quick Actions --}}
<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header py-3"><span class="fw-bold">Top Selling Medicines</span></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th class="ps-3">#</th><th>Product</th><th class="text-center">Qty</th><th class="text-end pe-3">Revenue</th></tr></thead>
                        <tbody>
                            @forelse($topProducts ?? [] as $i => $item)
                            <tr>
                                <td class="ps-3 text-muted fw-bold">{{ $i+1 }}</td>
                                <td class="fw-semibold small">{{ $item->product->name ?? 'N/A' }}</td>
                                <td class="text-center">{{ $item->total_qty }}</td>
                                <td class="text-end pe-3 small text-success">Rs. {{ number_format($item->total_revenue, 0) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No sales data yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header py-3"><span class="fw-bold text-warning">Low Stock Alert</span></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($lowStockProducts ?? [] as $product)
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <div>
                            <div class="fw-semibold small">{{ $product->name }}</div>
                            <div class="text-muted" style="font-size:.73rem;">{{ $product->category->name ?? 'N/A' }}</div>
                        </div>
                        <span class="badge bg-danger rounded-pill">{{ $product->stock }} left</span>
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted py-4">All medicines well stocked</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-header py-3"><span class="fw-bold text-danger">Expiry Alerts (60 Days)</span></div>
            <div class="card-body p-0" style="max-height: 250px; overflow-y: auto;">
                <ul class="list-group list-group-flush">
                    @forelse($expiringProducts ?? [] as $p)
                    @php $days = now()->diffInDays($p->expiry_date, false); @endphp
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <div>
                            <div class="fw-semibold small">{{ $p->name }}</div>
                            <div class="text-muted" style="font-size:.73rem;">Batch: {{ $p->batch_number ?: 'N/A' }} | Exp: {{ $p->expiry_date->format('d M Y') }}</div>
                        </div>
                        <span class="badge {{ $days <= 7 ? 'bg-danger' : 'bg-warning text-dark' }} rounded-pill">{{ $days }}d left</span>
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted py-4">No expiring medicines</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-3">
        <div class="card">
            <div class="card-header py-3"><span class="fw-bold">Quick Actions</span></div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm">New Invoice</a>
                <a href="{{ route('sales-returns.index') }}" class="btn btn-outline-warning btn-sm">Sales Return</a>
                <a href="{{ route('reports.cashier-eod') }}" class="btn btn-outline-info btn-sm">My Daily Report</a>
                @if(auth()->user()->isAdmin())
                <a href="{{ route('products.create') }}" class="btn btn-outline-secondary btn-sm">Add Medicine</a>
                <a href="{{ route('products.import') }}" class="btn btn-outline-secondary btn-sm">Import CSV</a>
                <a href="{{ route('reports.eod') }}" class="btn btn-outline-dark btn-sm">End of Day</a>
                @endif
            </div>
        </div>
        @if(auth()->user()->isAdmin())
        <div class="card mt-3">
            <div class="card-body text-center">
                <div class="text-muted small mb-1">Expenses This Month</div>
                <div class="fs-5 fw-bold text-danger">Rs. {{ number_format($totalExpenses ?? 0, 0) }}</div>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Recent Invoices --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <span class="fw-bold">Recent Invoices</span>
        <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th class="ps-3">Invoice #</th><th>Customer</th><th>Cashier</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($recentInvoices ?? [] as $invoice)
                    <tr>
                        <td class="ps-3"><a href="{{ route('invoices.show', $invoice) }}" class="text-decoration-none fw-semibold">#{{ str_pad($invoice->id, 4, '0', STR_PAD_LEFT) }}</a></td>
                        <td>{{ $invoice->customer->name ?? 'Walk-in' }}</td>
                        <td class="text-muted small">{{ $invoice->cashier->name ?? '—' }}</td>
                        <td class="text-muted small">{{ $invoice->created_at->format('d M Y h:i A') }}</td>
                        <td class="fw-semibold">Rs. {{ number_format($invoice->total, 0) }}</td>
                        <td>
                            <span class="badge rounded-pill {{ $invoice->status==='paid' ? 'badge-active' : ($invoice->status==='partial' ? 'badge-pending' : 'badge-inactive') }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No invoices yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Expiry Modal --}}
<div class="modal fade" id="expiryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-bold">Expiry Alerts</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                @if(($expiredProducts ?? collect())->isNotEmpty())
                <div class="px-3 pt-3 pb-1"><h6 class="text-danger fw-bold">Expired — Billing Disabled</h6></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th class="ps-3">Medicine</th><th>Batch</th><th>Expiry</th><th>Stock</th></tr></thead>
                        <tbody>
                            @foreach($expiredProducts as $p)
                            <tr class="table-danger">
                                <td class="ps-3 fw-semibold">{{ $p->name }}</td>
                                <td>{{ $p->batch_number ?: '—' }}</td>
                                <td>{{ $p->expiry_date->format('d M Y') }}</td>
                                <td>{{ $p->stock }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
                @if(($expiringProducts ?? collect())->isNotEmpty())
                <div class="px-3 pt-3 pb-1"><h6 class="text-warning fw-bold">Expiring Within 60 Days (2 Months)</h6></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th class="ps-3">Medicine</th><th>Batch</th><th>Expiry</th><th>Days Left</th><th>Stock</th></tr></thead>
                        <tbody>
                            @foreach($expiringProducts as $p)
                            @php $days = now()->diffInDays($p->expiry_date, false); @endphp
                            <tr class="{{ $days <= 7 ? 'table-danger' : 'table-warning' }}">
                                <td class="ps-3 fw-semibold">{{ $p->name }}</td>
                                <td>{{ $p->batch_number ?: '—' }}</td>
                                <td>{{ $p->expiry_date->format('d M Y') }}</td>
                                <td><span class="badge {{ $days<=7?'bg-danger':'bg-warning text-dark' }}">{{ $days }}d</span></td>
                                <td>{{ $p->stock }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
            <div class="modal-footer">
                @if(auth()->user()->isAdmin())
                <a href="{{ route('stock-returns.index') }}" class="btn btn-danger btn-sm">Go to Returns</a>
                @endif
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('offline/offline-chart.js') }}"></script>
<script>
(function () {
    var canvas = document.getElementById('salesChart');
    if (canvas) {
        renderOfflineBarChart(
            canvas,
            {!! json_encode(array_column($salesChart, 'date')) !!},
            {!! json_encode(array_column($salesChart, 'revenue')) !!}
        );
    }

})();
</script>
@endpush
