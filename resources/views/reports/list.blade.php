@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">{{ $title }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">{{ $title }}</li>
            </ol>
        </nav>
    </div>
    <button onclick="window.print()" class="btn btn-outline-secondary no-print"><i class="bi bi-printer me-2"></i>Print</button>
</div>

@if($showDateFilter ?? true)
<div class="card mb-4 no-print">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">From</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $from ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">To</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $to ?? '' }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>
@endif

@if(!empty($summaryCards))
<div class="row g-3 mb-4">
    @foreach($summaryCards as $card)
    <div class="col-md-3 col-sm-6">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small mb-1">{{ $card['label'] }}</div>
                <div class="fs-5 fw-bold text-{{ $card['color'] ?? 'dark' }}">{{ $card['value'] }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

<div class="card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <span class="fw-bold">{{ $title }}</span>
        <span class="text-muted small">{{ count($rows) }} record(s)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr class="bg-light">
                        @foreach($columns as $label)
                            <th class="ps-3">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            @foreach(array_keys($columns) as $key)
                                <td class="ps-3">{{ $row[$key] ?? '-' }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($columns) }}" class="text-center text-muted py-4">No records found.</td></tr>
                    @endforelse
                </tbody>
                @if(!empty($totals))
                <tfoot>
                    <tr class="fw-bold bg-light">
                        @foreach(array_keys($columns) as $key)
                            <td class="ps-3">{{ $totals[$key] ?? '' }}</td>
                        @endforeach
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
