@extends('layouts.app')

@section('title', 'Categories')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Categories</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Categories</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('categories.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Add Category
    </a>
</div>

{{-- Bulk action form wrapper --}}
<form id="bulkDeleteForm" action="{{ route('categories.bulkDestroy') }}" method="POST">
@csrf
@method('DELETE')

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center py-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span><i class="bi bi-tags me-2"></i>All Categories ({{ $categories->total() }})</span>
            {{-- Bulk Delete Button --}}
            <button type="submit" id="bulkDeleteBtn" class="btn btn-sm btn-danger d-none fw-bold shadow-sm" onclick="return confirm('Are you sure you want to delete selected categories? All medicines inside them will also be deleted!')">
                <i class="bi bi-trash-fill me-1"></i>Bulk Delete (<span id="selectedCount">0</span>)
            </button>
        </div>
        <div class="d-flex gap-2">
            <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search categories..." style="width:220px;">
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" id="categoriesTable">
                <thead>
                    <tr>
                        <th class="ps-3" style="width:40px;">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="selectAll">
                            </div>
                        </th>
                        <th style="width:50px;">#</th>
                        <th>Name</th>
                        <th>Product Type</th>
                        <th>Description</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                    <tr id="row_category_{{ $category->id }}">
                        <td class="ps-3">
                            <div class="form-check">
                                <input class="form-check-input category-checkbox" type="checkbox" name="ids[]" value="{{ $category->id }}">
                            </div>
                        </td>
                        <td class="text-muted">{{ $loop->iteration }}</td>
                        <td class="fw-bold text-dark">{{ $category->name }}</td>
                        <td>
                            @if($category->product_type === 'medicine')
                                <span class="badge bg-primary">Medicine</span>
                            @elseif($category->product_type === 'liquid')
                                <span class="badge bg-info text-dark">Liquid (Syrup)</span>
                            @else
                                <span class="badge bg-secondary">General Item</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ Str::limit($category->description, 60) ?? '—' }}</td>
                        <td>
                            <span class="badge bg-light text-dark border fw-bold">{{ $category->products_count ?? 0 }}</span>
                        </td>
                        <td>
                            @if($category->status ?? true)
                                <span class="badge rounded-pill badge-active">Active</span>
                            @else
                                <span class="badge rounded-pill badge-inactive">Inactive</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $category->created_at->format('d M Y') }}</td>
                        <td class="text-end pe-3">
                            <a href="{{ route('categories.show', $category) }}" class="btn btn-sm btn-outline-info me-1">View</a>
                            <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-outline-warning me-1">Edit</a>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteSingle({{ $category->id }})">Delete</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <i class="bi bi-tags fs-1 text-muted d-block mb-2"></i>
                            <p class="text-muted mb-0">No categories found.</p>
                            <a href="{{ route('categories.create') }}" class="btn btn-primary btn-sm mt-2">Add First Category</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($categories->hasPages())
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">
            Showing 
            <span class="mx-1 fw-semibold">{{ $categories->firstItem() }}</span> 
            to 
            <span class="mx-1 fw-semibold">{{ $categories->lastItem() }}</span> 
            of 
            <span class="mx-1 fw-semibold">{{ $categories->total() }}</span> 
            results
        </small>
        <div>
            {{ $categories->links('pagination::bootstrap-4') }}
        </div>
    </div>
    @endif
</div>

</form>

{{-- Single category delete fallback form --}}
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
    document.querySelectorAll('#categoriesTable tbody tr[id^="row_category_"]').forEach(row => {
        const text = row.textContent.toLowerCase();
        const matches = text.includes(search);
        row.style.display = matches ? '' : 'none';
        
        // If row is hidden by filter, uncheck it so it doesn't get bulk deleted accidentally
        if (!matches) {
            const cb = row.querySelector('.category-checkbox');
            if (cb) cb.checked = false;
        }
    });
    updateBulkDeleteState();
}

document.getElementById('searchInput').addEventListener('keyup', filterTable);

// Select All functionality (only checks visible rows)
const selectAllCheckbox = document.getElementById('selectAll');
selectAllCheckbox.addEventListener('change', function() {
    const isChecked = this.checked;
    document.querySelectorAll('#categoriesTable tbody tr[id^="row_category_"]').forEach(row => {
        if (row.style.display !== 'none') {
            const cb = row.querySelector('.category-checkbox');
            if (cb) cb.checked = isChecked;
        }
    });
    updateBulkDeleteState();
});

// Row checkbox change
document.querySelectorAll('.category-checkbox').forEach(cb => {
    cb.addEventListener('change', updateBulkDeleteState);
});

// Update bulk delete button visibility and selected count
function updateBulkDeleteState() {
    const checkboxes = document.querySelectorAll('.category-checkbox:checked');
    const totalVisible = Array.from(document.querySelectorAll('#categoriesTable tbody tr[id^="row_category_"]'))
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
    if (confirm('Are you sure you want to delete this category? All medicines inside this category will also be deleted!')) {
        const form = document.getElementById('singleDeleteForm');
        form.action = `/categories/${id}`;
        form.submit();
    }
}
</script>
@endpush
