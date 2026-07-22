@extends('layouts.app')

@section('title', 'Medicines')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Medicines</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Medicines</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('products.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Add Medicine
    </a>
</div>

{{-- Bulk action form wrapper --}}
<form id="bulkDeleteForm" action="{{ route('products.bulkDestroy') }}" method="POST">
@csrf
@method('DELETE')

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center py-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span><i class="bi bi-box-seam me-2"></i>All Medicines ({{ $products->total() }})</span>
            {{-- Bulk Delete Button --}}
            <button type="submit" id="bulkDeleteBtn" class="btn btn-sm btn-danger d-none fw-bold shadow-sm" onclick="return confirm('Are you sure you want to delete all selected medicines?')">
                <i class="bi bi-trash-fill me-1"></i>Bulk Delete (<span id="selectedCount">0</span>)
            </button>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <select name="expiry_filter" class="form-select form-select-sm" style="width:140px;" onchange="window.location.href='?expiry_filter='+this.value">
                <option value="">All Expiries</option>
                <option value="expired"  {{ request('expiry_filter')==='expired'  ? 'selected':'' }}>🔒 Expired</option>
                <option value="critical" {{ request('expiry_filter')==='critical'  ? 'selected':'' }}>🔴 Critical (&lt;1M)</option>
                <option value="warning"  {{ request('expiry_filter')==='warning'   ? 'selected':'' }}>🟡 Soon (&lt;3M)</option>
                <option value="ok"       {{ request('expiry_filter')==='ok'        ? 'selected':'' }}>🟢 OK (&gt;3M)</option>
            </select>
            <select class="form-select form-select-sm" id="categoryFilter" style="width:160px;">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search medicines..." style="width:200px;">
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" id="productsTable">
                <thead>
                    <tr>
                        <th class="ps-3" style="width:40px;">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="selectAll">
                            </div>
                        </th>
                        <th style="width:50px;">#</th>
                        <th>Medicine</th>
                        <th>Category</th>
                        <th>Batch / Expiry</th>
                        <th>Shelf</th>
                        <th>Buy Price</th>
                        <th>Sale Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr id="row_product_{{ $product->id }}">
                        <td class="ps-3">
                            <div class="form-check">
                                <input class="form-check-input product-checkbox" type="checkbox" name="ids[]" value="{{ $product->id }}">
                            </div>
                        </td>
                        <td class="text-muted">{{ $loop->iteration }}</td>
                        <td>
                            <div class="fw-bold text-dark">{{ $product->name }}</div>
                            @if($product->generic_name)
                                <div class="text-muted small">{{ $product->generic_name }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $product->category->name ?? '—' }}</span>
                        </td>
                        <td>
                            @if($product->batch_number)
                                <div class="small text-muted">{{ $product->batch_number }}</div>
                            @endif
                            @if($product->expiry_date)
                                {!! $product->expiryBadgeHtml() !!}
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            @if($product->almari || $product->khana || $product->row)
                                <div class="small">
                                    {{ $product->almari ? $product->almari : '' }}
                                    {{ $product->khana ? ', ' . $product->khana : '' }}
                                    {{ $product->row ? ', ' . $product->row : '' }}
                                </div>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="text-muted">Rs. {{ number_format($product->buy_price ?? 0, 2) }}</td>
                        <td class="fw-semibold">Rs. {{ number_format($product->price, 2) }}</td>
                        <td>
                            <span class="badge rounded-pill {{ $product->stock <= ($product->low_stock_threshold ?? 0) ? 'bg-danger' : ($product->stock <= (($product->low_stock_threshold ?? 0) * 2) ? 'bg-warning text-dark' : 'bg-success') }}">
                                {{ $product->formattedStock() }}
                            </span>
                        </td>
                        <td>
                            @if($product->status ?? true)
                                <span class="badge rounded-pill badge-active">Active</span>
                            @else
                                <span class="badge rounded-pill badge-inactive">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-outline-info me-1">View</a>
                            <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-warning me-1">Edit</a>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteSingle({{ $product->id }})">Delete</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center py-5">
                            <i class="bi bi-box-seam fs-1 text-muted d-block mb-2"></i>
                            <p class="text-muted mb-0">No medicines found.</p>
                            <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm mt-2">Add First Medicine</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($products->hasPages())
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }}</small>
        {{ $products->links() }}
    </div>
    @endif
</div>

</form>

{{-- Single product delete fallback form --}}
<form id="singleDeleteForm" action="" method="POST" style="display:none">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
// Filter display helper
function filterTable() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const cat = document.getElementById('categoryFilter').value.toLowerCase();
    document.querySelectorAll('#productsTable tbody tr[id^="row_product_"]').forEach(row => {
        const text = row.textContent.toLowerCase();
        const matches = text.includes(search) && (!cat || text.includes(cat));
        row.style.display = matches ? '' : 'none';
        
        // If row is hidden by filter, uncheck it so it doesn't get bulk deleted accidentally
        if (!matches) {
            const cb = row.querySelector('.product-checkbox');
            if (cb) cb.checked = false;
        }
    });
    updateBulkDeleteState();
}

document.getElementById('searchInput').addEventListener('keyup', filterTable);
document.getElementById('categoryFilter').addEventListener('change', filterTable);

// Select All functionality (only checks visible rows)
const selectAllCheckbox = document.getElementById('selectAll');
selectAllCheckbox.addEventListener('change', function() {
    const isChecked = this.checked;
    document.querySelectorAll('#productsTable tbody tr[id^="row_product_"]').forEach(row => {
        if (row.style.display !== 'none') {
            const cb = row.querySelector('.product-checkbox');
            if (cb) cb.checked = isChecked;
        }
    });
    updateBulkDeleteState();
});

// Row checkbox change
document.querySelectorAll('.product-checkbox').forEach(cb => {
    cb.addEventListener('change', updateBulkDeleteState);
});

// Update bulk delete button visibility and selected count
function updateBulkDeleteState() {
    const checkboxes = document.querySelectorAll('.product-checkbox:checked');
    const totalVisible = Array.from(document.querySelectorAll('#productsTable tbody tr[id^="row_product_"]'))
        .filter(r => r.style.display !== 'none').length;
    
    const selectAll = document.getElementById('selectAll');
    if (checkboxes.length === 0) {
        selectAll.checked = false;
        selectAll.indeterminate = false;
    } else if (checkboxes.length === totalVisible) {
        selectAll.checked = true;
        selectAll.indeterminate = false;
    } else {
        selectAll.checked = false;
        selectAll.indeterminate = true;
    }

    const btn = document.getElementById('bulkDeleteBtn');
    const countSpan = document.getElementById('selectedCount');
    if (checkboxes.length > 0) {
        btn.classList.remove('d-none');
        countSpan.textContent = checkboxes.length;
    } else {
        btn.classList.add('d-none');
    }
}

// Single delete execution
function deleteSingle(id) {
    if (confirm('Are you sure you want to delete this medicine?')) {
        const form = document.getElementById('singleDeleteForm');
        form.action = `/products/${id}`;
        form.submit();
    }
}
</script>
@endpush
