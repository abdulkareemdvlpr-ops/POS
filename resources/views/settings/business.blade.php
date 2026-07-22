@extends('layouts.app')

@section('title', 'Business Settings')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Business Settings</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Business Settings</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header py-3 fw-bold">Business Information</div>
            <div class="card-body">
                <form action="{{ route('business-settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="row g-3">

                        {{-- ── Pharmacy Identity ── --}}
                        <div class="col-12">
                            <h6 class="fw-bold mb-0">Pharmacy Identity</h6>
                            <p class="text-muted small mb-0">These details appear on the bill header.</p>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Pharmacy Name <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" name="business_name" class="form-control @error('business_name') is-invalid @enderror"
                                value="{{ old('business_name', $businessSetting->business_name) }}"
                                placeholder="e.g. City Pharmacy">
                            @error('business_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone', $businessSetting->phone) }}" placeholder="0300-1234567">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Home Delivery Phone</label>
                            <input type="text" name="delivery_phone" class="form-control"
                                value="{{ old('delivery_phone', $businessSetting->delivery_phone) }}" placeholder="0312-0000000">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Sales Tax (%)</label>
                            <input type="number" name="sales_tax_rate" step="0.01" min="0" max="100"
                                class="form-control" value="{{ old('sales_tax_rate', $businessSetting->sales_tax_rate ?? 0) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Service Fee (Rs.)</label>
                            <input type="number" name="service_fee" step="0.01" min="0"
                                class="form-control" value="{{ old('service_fee', $businessSetting->service_fee ?? 0) }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Address</label>
                            <textarea name="address" rows="2" class="form-control"
                                placeholder="Pharmacy address">{{ old('address', $businessSetting->address) }}</textarea>
                        </div>

                        {{-- ── Header Toggles ── --}}
                        <div class="col-12 border-top pt-3 mt-2">
                            <h6 class="fw-bold mb-0">Dynamic Header — Show / Hide on Print</h6>
                            <p class="text-muted small mb-0">Toggle which fields appear on the printed receipt.</p>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-semibold">License #</label>
                            <input type="text" name="license_no" class="form-control"
                                value="{{ old('license_no', $businessSetting->license_no) }}" placeholder="e.g. 08-384-0120-023860P">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" name="show_license" value="1"
                                    id="show_license" {{ old('show_license', $businessSetting->show_license) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="show_license">Print on Receipt</label>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-semibold">NTN #</label>
                            <input type="text" name="ntn_no" class="form-control"
                                value="{{ old('ntn_no', $businessSetting->ntn_no) }}" placeholder="e.g. 0150979-9">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" name="show_ntn" value="1"
                                    id="show_ntn" {{ old('show_ntn', $businessSetting->show_ntn) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="show_ntn">Print on Receipt</label>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-semibold">STRN #</label>
                            <input type="text" name="strn_no" class="form-control"
                                value="{{ old('strn_no', $businessSetting->strn_no) }}" placeholder="e.g. 3277876193881">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" name="show_strn" value="1"
                                    id="show_strn" {{ old('show_strn', $businessSetting->show_strn) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="show_strn">Print on Receipt</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" name="show_phone_on_print" value="1"
                                    id="show_phone_on_print" {{ old('show_phone_on_print', $businessSetting->show_phone_on_print ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="show_phone_on_print">Print Phone Number on Receipt</label>
                            </div>
                        </div>

                        {{-- ── Front Logo ── --}}
                        <div class="col-12 border-top pt-3 mt-2">
                            <h6 class="fw-bold mb-0">Front Logo</h6>
                            <p class="text-muted small mb-0">Appears at the top of the front side of the receipt.</p>
                        </div>
                        <div class="col-12">
                            @if($businessSetting->logo_path)
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <img src="{{ route('media.show', $businessSetting->logo_path) }}" alt="Front logo"
                                        style="height:64px;max-width:160px;object-fit:contain;border:1px solid #e5e7eb;border-radius:6px;padding:5px;background:#fff;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="remove_logo">
                                        <label class="form-check-label text-muted small" for="remove_logo">Remove current logo</label>
                                    </div>
                                </div>
                            @endif
                            <input type="file" name="logo" class="form-control @error('logo') is-invalid @enderror" accept="image/*">
                            <div class="form-text">Optional. Max 2MB. JPG, PNG, WebP.</div>
                            @error('logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        {{-- ── Receipt Instructions ── --}}
                        <div class="col-12 border-top pt-3 mt-2">
                            <h6 class="fw-bold mb-0">Receipt Instructions / Terms</h6>
                            <p class="text-muted small mb-0">Heading and instructions printed at the bottom of the front receipt.</p>
                        </div>

                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Instructions Heading</label>
                            <input type="text" name="receipt_back_heading"
                                class="form-control"
                                style="{{ ($businessSetting->receipt_back_rtl ?? false) ? 'direction:rtl; font-family:\'Jameel Noori Nastaleeq\', \'Urdu Typesetting\', Tahoma, Arial, sans-serif; font-size:1.1rem; letter-spacing:normal !important;' : '' }}"
                                value="{{ old('receipt_back_heading', $businessSetting->receipt_back_heading ?? 'توجہ فرمائیں') }}">
                        </div>
                        <div class="col-md-5 d-flex align-items-end pb-1">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" name="receipt_back_rtl" value="1"
                                    id="receipt_back_rtl" {{ old('receipt_back_rtl', $businessSetting->receipt_back_rtl ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="receipt_back_rtl">
                                    Enable Urdu / RTL Mode
                                </label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Receipt Instructions</label>
                            <textarea name="receipt_back_notes" rows="8"
                                class="form-control"
                                placeholder="Enter each instruction on a new line. For Urdu, type in Urdu directly."
                                style="{{ ($businessSetting->receipt_back_rtl ?? false) ? 'direction:rtl; font-family:\'Jameel Noori Nastaleeq\', \'Urdu Typesetting\', Tahoma, Arial, sans-serif; font-size:1.1rem; line-height:2; letter-spacing:normal !important;' : '' }}"
                                >{{ old('receipt_back_notes', $businessSetting->receipt_back_notes) }}</textarea>
                            <div class="form-text">One instruction per line. These will print at the bottom of the bill.</div>
                        </div>

                        {{-- Immutable Branding --}}
                        <div class="col-12 border-top pt-3 mt-2">
                            <label class="form-label fw-semibold">Locked Software Credit</label>
                            <div class="form-control bg-light text-muted">{{ \App\Models\BusinessSetting::SOFTWARE_CREDIT }}</div>
                            <div class="form-text">This line is hard-coded and cannot be removed.</div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Save Settings
                        </button>
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Danger Zone: Selected Data Cleanup --}}
        <div class="card border-danger mt-4">
            <div class="card-header bg-danger text-white py-3 fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Danger Zone (Admin Only)</div>
            <div class="card-body">
                <h6 class="fw-bold text-danger">Delete Selected Old / Full Data</h6>
                <p class="text-muted small mb-3">Choose exactly which records to remove. Users, business settings, logo, and software settings will stay safe.</p>

                <form action="{{ route('business-settings.cleanup-selected-data') }}" method="POST" onsubmit="return confirm('WARNING: This will permanently delete the selected data. Continue only if you have a backup.');">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted d-block">Cleanup Scope <span class="text-danger">*</span></label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="cleanup_scope" id="cleanup_scope_old" value="older_than" checked>
                            <label class="form-check-label" for="cleanup_scope_old">Delete old data up to selected date</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="cleanup_scope" id="cleanup_scope_all" value="all_selected">
                            <label class="form-check-label" for="cleanup_scope_all">Delete all selected data</label>
                        </div>
                    </div>

                    <div class="mb-3" id="cleanupDateWrap">
                        <label class="form-label small text-muted">Delete records dated on or before: <span class="text-danger">*</span></label>
                        <input type="date" name="cutoff_date" class="form-control form-control-sm" style="width:250px;" value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="row g-2 mb-3">
                        @php
                            $cleanupOptions = [
                                'billing_invoices' => 'Billing Invoices',
                                'held_bills' => 'Held Bills',
                                'sales_returns' => 'Sales Returns',
                                'purchases' => 'Local Distributor Purchases / Khata',
                                'supplier_payments' => 'Supplier Kisht Payments',
                                'customer_payments' => 'Customer Collections',
                                'expenses' => 'Expenses',
                                'cash_registers' => 'Cash Register Shifts',
                                'stock_returns' => 'Returns / Damaged Stock',
                                'cash_to_bank_logs' => 'Cash To Bank Logs',
                            ];
                        @endphp
                        @foreach($cleanupOptions as $value => $label)
                            <div class="col-md-6">
                                <div class="form-check border rounded px-3 py-2 h-100" style="padding-left:2.25rem !important;">
                                    <input class="form-check-input" type="checkbox" name="data_types[]" value="{{ $value }}" id="cleanup_{{ $value }}">
                                    <label class="form-check-label small fw-semibold" for="cleanup_{{ $value }}">{{ $label }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">To confirm deletion, type <strong class="text-dark">DELETE SELECTED DATA</strong> below:</label>
                        <input type="text" name="confirm_cleanup" class="form-control form-control-sm" placeholder="DELETE SELECTED DATA" required style="width:280px;">
                    </div>

                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash-fill me-1"></i>Delete Selected Data Permanently
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const oldScope = document.getElementById('cleanup_scope_old');
    const allScope = document.getElementById('cleanup_scope_all');
    const dateWrap = document.getElementById('cleanupDateWrap');
    const cutoffInput = dateWrap ? dateWrap.querySelector('input[name="cutoff_date"]') : null;

    function syncCleanupDate() {
        const showDate = oldScope && oldScope.checked;
        if (dateWrap) dateWrap.style.display = showDate ? '' : 'none';
        if (cutoffInput) cutoffInput.required = showDate;
    }

    if (oldScope) oldScope.addEventListener('change', syncCleanupDate);
    if (allScope) allScope.addEventListener('change', syncCleanupDate);
    syncCleanupDate();
});
</script>
@endpush
