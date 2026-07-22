@extends('layouts.app')

@section('title', 'Add Category')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Add Category</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('categories.index') }}">Categories</a></li>
                <li class="breadcrumb-item active">Add</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header py-3">
                <i class="bi bi-tags me-2"></i>Category Information
            </div>
            <div class="card-body">
                <form action="{{ route('categories.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}" placeholder="e.g. Tablets, Syrups, General Items..." required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Product Type / Packaging Template <span class="text-danger">*</span></label>
                        <select name="product_type" class="form-select @error('product_type') is-invalid @enderror" required>
                            <option value="medicine" {{ old('product_type', 'medicine') === 'medicine' ? 'selected' : '' }}>Medicine (Box / Strip / Tablet)</option>
                            <option value="liquid" {{ old('product_type') === 'liquid' ? 'selected' : '' }}>Syrup / Liquid / Cream / Injection / Drops</option>
                            <option value="general" {{ old('product_type') === 'general' ? 'selected' : '' }}>General Item (single piece)</option>
                        </select>
                        <div class="form-text text-muted small">This determines what pricing and packaging fields are displayed for products in this category.</div>
                        @error('product_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror"
                            placeholder="Optional description...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Default Location Fields --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-geo-alt me-1 text-primary"></i>Default Storage Location
                            <span class="text-muted small fw-normal ms-1">(Auto-filled when adding medicine)</span>
                        </label>
                        <div class="p-3 rounded" style="background:#f0f4ff; border:1px solid #c7d7ff;">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">Almari (Cupboard)</label>
                                    <input type="text" name="default_almari" class="form-control form-control-sm @error('default_almari') is-invalid @enderror"
                                        value="{{ old('default_almari') }}" placeholder="e.g. Almari 3">
                                    @error('default_almari')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">Khana (Drawer/Box)</label>
                                    <input type="text" name="default_khana" class="form-control form-control-sm @error('default_khana') is-invalid @enderror"
                                        value="{{ old('default_khana') }}" placeholder="e.g. Khana 2">
                                    @error('default_khana')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">Row (Shelf Row)</label>
                                    <input type="text" name="default_row" class="form-control form-control-sm @error('default_row') is-invalid @enderror"
                                        value="{{ old('default_row') }}" placeholder="e.g. Row 1">
                                    @error('default_row')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="mt-2 small text-muted"><i class="bi bi-info-circle me-1"></i>These locations will be pre-filled automatically when a new medicine is added under this category.</div>
                        </div>
                    </div>

                    {{-- Default Dates Fields --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-calendar-event me-1 text-primary"></i>Default Batch Dates
                            <span class="text-muted small fw-normal ms-1">(Updates all medicines in this category)</span>
                        </label>
                        <div class="p-3 rounded" style="background:#fefefe; border:1px solid #c7d7ff;">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold mb-1">Mfg Date (Manufacturing)</label>
                                    <input type="date" name="default_mfg_date" class="form-control form-control-sm @error('default_mfg_date') is-invalid @enderror"
                                        value="{{ old('default_mfg_date') }}">
                                    @error('default_mfg_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold mb-1">Expiry Date</label>
                                    <input type="date" name="default_expiry_date" class="form-control form-control-sm @error('default_expiry_date') is-invalid @enderror"
                                        value="{{ old('default_expiry_date') }}">
                                    @error('default_expiry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="1" {{ old('status', '1') == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>


                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Save Category
                        </button>
                        <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
