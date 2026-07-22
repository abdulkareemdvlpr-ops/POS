@extends('layouts.app')

@section('title', 'Open Cash Register')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow border-0" style="border-radius: 16px; overflow: hidden; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #f8fafc;">
                <div class="card-body p-4 text-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 70px; height: 70px; background: rgba(14, 165, 233, 0.15); color: #0ea5e9;">
                        <i class="bi bi-wallet2" style="font-size: 2.2rem;"></i>
                    </div>
                    <h3 class="fw-bold mb-1">Open Cash Register</h3>
                    <p class="text-white-50 small mb-4">Enter the starting cash present in the shared galla. Admin or cashier can open it, and either can close it.</p>

                    <form action="{{ route('cash-registers.store') }}" method="POST" class="text-start">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label text-white-50 fw-semibold" style="font-size: 0.85rem;">Opening Float (Rs.) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg shadow-sm border border-secondary rounded" style="background: rgba(255,255,255,0.05);">
                                <span class="input-group-text border-0 bg-transparent text-white-50">Rs.</span>
                                <input type="number" step="0.01" name="opening_amount" class="form-control border-0 bg-transparent text-white fw-bold shadow-none" placeholder="0.00" min="0" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-white-50 fw-semibold" style="font-size: 0.85rem;">Opening Notes / Remarks</label>
                            <textarea name="notes" class="form-control border-secondary text-white bg-transparent shadow-none" rows="3" placeholder="Galla float notes (e.g. details of cash change)..." style="background: rgba(255,255,255,0.02) !important;"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow py-3" style="border-radius: 12px; background: #0ea5e9; border: none;">
                            <i class="bi bi-play-fill me-1"></i>Open Register & Start Selling
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
