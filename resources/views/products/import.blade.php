@extends('layouts.app')

@section('title', 'Bulk Bill Import (CSV/Excel)')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Bulk Bill Import (CSV/Excel)</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Medicines</a></li>
                <li class="breadcrumb-item active">Bulk Import</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <span class="fw-bold text-dark fs-5"><i class="bi bi-file-earmark-spreadsheet me-2 text-success"></i>Upload CSV or Excel File</span>
            <div class="text-muted small">Required: Product Name, Single Unit Buy Price, Single Unit Sale Price. Everything else is optional.</div>
        </div>
        <a href="{{ route('products.import.template') }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-download me-1"></i>Download Template
        </a>
    </div>
    <div class="card-body">
        <div class="alert alert-info small mb-3">
            <strong><i class="bi bi-info-circle me-1"></i>How matching &amp; stock works:</strong>
            A row updates an existing product if its <strong>SKU</strong>, then <strong>Barcode</strong>,
            then exact <strong>Name</strong> matches one already in your inventory — otherwise a new
            product is created. <strong>Total Boxes Added is ADDED to the current stock</strong> (it
            never overwrites what you already have).
            <br><br>
            <strong>Product Types &amp; Packaging Rules:</strong>
            <ul>
                <li><strong>medicine</strong>: Enter <em>Strips Per Box</em> &amp; <em>Tablets Per Strip</em>. Stock is converted to tablets: <code>Boxes × Strips Per Box × Tablets Per Strip</code>.</li>
                <li><strong>liquid</strong>: Enter <em>Units Per Box</em> (carton size, e.g. 12) &amp; <em>Volume</em> (e.g. 120ml). Stock is converted to bottles/pieces: <code>Boxes × Units Per Box</code>.</li>
                <li><strong>general</strong>: Always sold as single-piece items. All multipliers are treated as 1.</li>
            </ul>
            <strong>Box-First Pricing &amp; Trade Discount:</strong>
            Instead of entering single unit prices, you can enter <strong>Box Buy Price</strong> and <strong>Box MRP</strong>.
            If you omit the Box Buy Price but provide <strong>Box MRP</strong> and a <strong>Trade Discount %</strong> (e.g., 14.5% or 15%), the system will automatically calculate the purchase price and save calculated single tablet/unit prices to the database.
            <br>
            Multi-sheet Excel files are supported; the correct sheet is detected automatically even if instruction pages come first.
        </div>
        <form action="{{ route('products.import.store') }}" method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
            @csrf
            <div class="col-lg-8">
                <label class="form-label fw-semibold">Bulk Bill File</label>
                <input type="file" name="import_file" class="form-control" accept=".csv,.txt,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                <div class="form-text">Accepted: CSV and Excel .xlsx files. Extra/unknown columns are ignored.</div>
            </div>
            <div class="col-lg-4 text-lg-end">
                <button type="submit" class="btn btn-success fw-bold px-4">
                    <i class="bi bi-upload me-2"></i>Import File to Stock
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <span class="fw-bold text-success fs-5"><i class="bi bi-card-checklist me-2"></i>Manual Bulk Entry</span>
            <div class="text-muted small">Add medicines here when a bill file is not available.</div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-success" onclick="addNewItemRow()">
            <i class="bi bi-plus-circle me-1"></i>Add Row
        </button>
    </div>
    <div class="card-body p-0">
        <form action="{{ route('products.import.store') }}" method="POST" id="manualImportForm">
            @csrf
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0" id="manualImportTable">
                    <thead>
                        <tr class="bg-light text-muted small text-uppercase">
                            <th class="ps-3" style="width: 40px;">#</th>
                            <th style="min-width: 200px;">Medicine Name *</th>
                            <th style="min-width: 150px;">Generic Formula</th>
                            <th style="min-width: 110px;">SKU</th>
                            <th style="min-width: 110px;">Barcode</th>
                            <th style="min-width: 140px;">Category</th>
                            <th style="min-width: 90px;">Unit</th>
                            <th style="min-width: 100px;">Cost (Buy) *</th>
                            <th style="min-width: 100px;">Retail (Sale) *</th>
                            <th style="min-width: 90px;">Stock *</th>
                            <th style="min-width: 100px;">Batch #</th>
                            <th style="min-width: 130px;">Expiry Date</th>
                            <th class="text-center pe-3" style="width: 50px;">Remove</th>
                        </tr>
                    </thead>
                    <tbody id="manualImportTableBody"></tbody>
                </table>
            </div>
            <div class="card-footer bg-light p-3 text-end">
                <a href="{{ route('products.import') }}" class="btn btn-outline-secondary me-2">Clear</a>
                <button type="submit" class="btn btn-success fw-bold px-4">
                    <i class="bi bi-check-circle-fill me-2"></i>Save Manual Rows to Stock
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
.editable-input {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 0.85rem;
    width: 100%;
    transition: all 0.2s;
}
.editable-input:focus {
    outline: none;
    border-color: #22c55e;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
}
</style>
@endpush

@push('scripts')
<script>
const categories = @json($categories);
let itemIndex = 0;

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function categoryOptions(selectedId = '') {
    let options = '<option value="">Uncategorized</option>';
    categories.forEach(category => {
        const selected = String(category.id) === String(selectedId) ? 'selected' : '';
        options += `<option value="${category.id}" ${selected}>${escapeHtml(category.name)}</option>`;
    });
    return options;
}

function addItemRowWithData(data = {}) {
    const tbody = document.getElementById('manualImportTableBody');
    const rowId = itemIndex;
    const tr = document.createElement('tr');
    tr.id = `row-${rowId}`;
    tr.innerHTML = `
        <td class="ps-3 text-muted fw-bold row-num"></td>
        <td><input type="text" name="products[${rowId}][name]" value="${escapeHtml(data.name)}" class="editable-input" required></td>
        <td><input type="text" name="products[${rowId}][generic_name]" value="${escapeHtml(data.generic_name)}" class="editable-input"></td>
        <td><input type="text" name="products[${rowId}][sku]" value="${escapeHtml(data.sku)}" class="editable-input"></td>
        <td><input type="text" name="products[${rowId}][barcode]" value="${escapeHtml(data.barcode)}" class="editable-input"></td>
        <td><select name="products[${rowId}][category_id]" class="editable-input">${categoryOptions(data.category_id)}</select></td>
        <td><input type="text" name="products[${rowId}][unit]" value="${escapeHtml(data.unit ?? 'pcs')}" class="editable-input"></td>
        <td><input type="number" step="0.01" min="0" name="products[${rowId}][buy_price]" value="${escapeHtml(data.buy_price ?? 0)}" class="editable-input" required style="width: 90px;"></td>
        <td><input type="number" step="0.01" min="0" name="products[${rowId}][price]" value="${escapeHtml(data.price ?? 0)}" class="editable-input" required style="width: 90px;"></td>
        <td><input type="number" min="0" name="products[${rowId}][stock]" value="${escapeHtml(data.stock ?? 0)}" class="editable-input" required style="width: 80px;"></td>
        <td><input type="text" name="products[${rowId}][batch_number]" value="${escapeHtml(data.batch_number)}" class="editable-input"></td>
        <td><input type="date" name="products[${rowId}][expiry_date]" value="${escapeHtml(data.expiry_date)}" class="editable-input"></td>
        <td class="text-center pe-3">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(${rowId})">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    itemIndex++;
    updateRowNumbers();
}

function addNewItemRow() {
    addItemRowWithData();
}

function removeRow(idx) {
    const row = document.getElementById(`row-${idx}`);
    if (row) {
        row.remove();
        updateRowNumbers();
    }

    if (document.querySelectorAll('#manualImportTableBody tr').length === 0) {
        addNewItemRow();
    }
}

function updateRowNumbers() {
    document.querySelectorAll('#manualImportTableBody tr').forEach((row, i) => {
        row.querySelector('.row-num').textContent = i + 1;
    });
}

document.addEventListener('DOMContentLoaded', () => addNewItemRow());
</script>
@endpush