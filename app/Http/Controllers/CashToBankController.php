<?php
namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\CashToBankLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashToBankController extends Controller
{
    public function index()
    {
        $logs = CashToBankLog::with('creator')->latest()->paginate(20);
        $settings = BusinessSetting::current();
        return view('cash-to-bank.index', compact('logs', 'settings'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'bank_name' => 'required|string|max:255',
            'slip_number' => 'nullable|string|max:100',
            'deposit_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($data) {
            $data['created_by'] = auth()->id();
            CashToBankLog::create($data);
            $settings = BusinessSetting::current();
            $settings->cash_in_hand = max(0, ($settings->cash_in_hand ?? 0) - $data['amount']);
            $settings->bank_balance = ($settings->bank_balance ?? 0) + $data['amount'];
            $settings->save();
        });

        return back()->with('success', 'Cash deposited to bank. Balances updated.');
    }
}
