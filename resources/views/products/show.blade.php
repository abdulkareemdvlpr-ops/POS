@extends('layouts.app')

@section('title', 'Medicine Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Medicine Details</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Medicines</a></li>
                <li class="breadcrumb-item active">{{ $product->name }}</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('products.edit', $product) }}" class="btn btn-warning">
            <i class="bi bi-pencil me-2"></i>Edit
        </a>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center py-4">
                @if($product->image)
                    <img src="{{ route('media.show', $product->image) }}" class="rounded-3 mb-3" style="max-width:200px;max-height:200px;object-fit:cover;">
                @else
                    <div class="rounded-3 d-flex align-items-center justify-content-center mx-auto mb-3"
                        style="width:120px;height:120px;background:#f1f5f9;font-size:3rem;color:#94a3b8;">
                        <i class="bi bi-box-seam"></i>
                    </div>
                @endif
                <h5 class="fw-bold mb-1">{{ $product->name }}</h5>
                <p class="text-muted small mb-2">SKU: {{ $product->sku ?? 'N/A' }}</p>
                @if($product->status ?? true)
                    <span class="badge rounded-pill badge-active">Active</span>
                @else
                    <span class="badge rounded-pill badge-inactive">Inactive</span>
                @endif
            </div>
            <div class="card-footer">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-muted">Category</td>
                        <td class="fw-semibold">{{ $product->category->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Supplier</td>
                        <td class="fw-semibold">{{ $product->supplier->company_name ?? $product->supplier->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Buy Price</td>
                        <td class="fw-semibold">Rs. {{ number_format($product->buy_price ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">MRP / Sale Price</td>
                        <td class="fw-bold text-primary fs-6">Rs. {{ number_format($product->price, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Unit</td>
                        <td class="fw-semibold">{{ strtoupper($product->unit ?? 'pcs') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Product Type</td>
                        <td class="fw-semibold">
                            @if($product->product_type === 'medicine')
                                Medicine (Box/Strip/Tablet)
                            @elseif($product->product_type === 'liquid')
                                Liquid (Syrup/Cream/Drops)
                            @else
                                General Item
                            @endif
                        </td>
                    </tr>
                    @if($product->product_type === 'medicine')
                        <tr>
                            <td class="text-muted">Tablets/Strip</td>
                            <td class="fw-semibold">{{ $product->tablets_per_strip }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Strips/Box</td>
                            <td class="fw-semibold">{{ $product->strips_per_box }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Strip Prices</td>
                            <td class="fw-semibold">
                                Buy: Rs. {{ number_format($product->stripBuyPrice(), 2) }} <br>
                                Sale: Rs. {{ number_format($product->stripSalePrice(), 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Box Prices</td>
                            <td class="fw-semibold">
                                Buy: Rs. {{ number_format($product->boxBuyPrice(), 2) }} <br>
                                Sale: Rs. {{ number_format($product->boxSalePrice(), 2) }}
                            </td>
                        </tr>
                    @elseif($product->product_type === 'liquid')
                        <tr>
                            <td class="text-muted">Units/Box</td>
                            <td class="fw-semibold">{{ $product->units_per_box }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Volume</td>
                            <td class="fw-semibold">{{ $product->volume ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Box Prices</td>
                            <td class="fw-semibold">
                                Buy: Rs. {{ number_format($product->boxBuyPrice(), 2) }} <br>
                                Sale: Rs. {{ number_format($product->boxSalePrice(), 2) }}
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td class="text-muted">Stock</td>
                        <td>
                            <span class="badge rounded-pill {{ $product->stock <= ($product->low_stock_threshold ?? 0) ? 'bg-danger' : ($product->stock <= (($product->low_stock_threshold ?? 0) * 2) ? 'bg-warning text-dark' : 'bg-success') }}">
                                {{ $product->formattedStock() }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Low Alert</td>
                        <td class="fw-semibold">
                            {{ number_format($product->lowStockBoxes(), floor($product->lowStockBoxes()) == $product->lowStockBoxes() ? 0 : 2) }}
                            {{ $product->isGeneral() ? strtoupper($product->unit ?? 'pcs') : 'Boxes' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Added</td>
                        <td class="small">{{ $product->created_at->format('d M Y') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        @if($product->description)
        <div class="card mb-4">
            <div class="card-header py-3"><i class="bi bi-card-text me-2"></i>Description</div>
            <div class="card-body text-muted">{{ $product->description }}</div>
        </div>
        @endif

        <div class="card">
            <div class="card-header py-3"><i class="bi bi-graph-up me-2"></i>Profit Analysis</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-4 text-center">
                        <div class="p-3 rounded" style="background:#f0fdf4;">
                            <div class="fs-4 fw-bold text-success">
                                Rs. {{ number_format($product->price - ($product->buy_price ?? 0), 2) }}
                            </div>
                            <div class="text-muted small">Profit per unit</div>
                        </div>
                    </div>
                    <div class="col-sm-4 text-center">
                        <div class="p-3 rounded" style="background:#eff6ff;">
                            <div class="fs-4 fw-bold text-primary">
                                {{ $product->buy_price > 0 ? number_format((($product->price - $product->buy_price) / $product->buy_price) * 100, 1) : 0 }}%
                            </div>
                            <div class="text-muted small">Profit margin</div>
                        </div>
                    </div>
                    <div class="col-sm-4 text-center">
                        <div class="p-3 rounded" style="background:#fef9c3;">
                            <div class="fs-4 fw-bold text-warning">
                                Rs. {{ number_format(($product->price - ($product->buy_price ?? 0)) * $product->stock, 2) }}
                            </div>
                            <div class="text-muted small">Potential profit</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
