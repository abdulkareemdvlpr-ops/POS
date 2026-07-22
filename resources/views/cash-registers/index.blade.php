@extends('layouts.app')

@section('title', 'Cash Register Logs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Cash Register Shifts History Log</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Register Logs</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3"><span class="fw-bold"><i class="bi bi-clock-history me-2"></i>All Register Shifts</span></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Cashier</th>
                        <th>Opened At</th>
                        <th>Closed At</th>
                        <th class="text-end">Opening Float</th>
                        <th class="text-end">Expected Drawer</th>
                        <th class="text-end">Actual Closed</th>
                        <th class="text-end">Difference</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th class="text-end pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="ps-3"><a href="{{ route('cash-registers.show', $log) }}" class="fw-semibold text-decoration-none">{{ $log->id }}</a></td>
                        <td class="fw-semibold text-dark">{{ $log->user->name }}</td>
                        <td class="small text-muted">{{ $log->opened_at->format('d M Y h:i A') }}</td>
                        <td class="small text-muted">{{ $log->closed_at ? $log->closed_at->format('d M Y h:i A') : 'Active Shift' }}</td>
                        <td class="text-end fw-semibold text-secondary">Rs. {{ number_format($log->opening_amount, 2) }}</td>
                        <td class="text-end fw-semibold text-info">Rs. {{ number_format($log->expected_closing_amount, 2) }}</td>
                        <td class="text-end fw-bold text-dark">Rs. {{ number_format($log->actual_closing_amount, 2) }}</td>
                        <td class="text-end fw-bold">
                            @if($log->difference_amount == 0)
                                <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Balanced</span>
                            @elseif($log->difference_amount > 0)
                                <span class="text-success">+ Rs. {{ number_format($log->difference_amount, 2) }} (Surplus)</span>
                            @else
                                <span class="text-danger">- Rs. {{ number_format(abs($log->difference_amount), 2) }} (Deficit)</span>
                            @endif
                        </td>
                        <td>
                            @if($log->status === 'open')
                                <span class="badge bg-success rounded-pill px-2 py-1">Active</span>
                            @else
                                <span class="badge bg-secondary rounded-pill px-2 py-1">Closed</span>
                            @endif
                        </td>
                        <td class="small text-muted text-wrap" style="max-width: 200px;">{{ $log->notes ?? '--' }}</td>
                        <td class="text-end pe-3">
                            <a href="{{ route('cash-registers.show', $log) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>Details
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center py-5 text-muted">No cash register logs found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
    <div class="card-footer bg-white">
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection
