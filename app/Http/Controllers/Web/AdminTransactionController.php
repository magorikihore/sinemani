<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MobilePayment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = MobilePayment::with('user:id,name,email,phone');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('operator')) {
            $query->where('operator', $request->operator);
        }
        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
                  ->orWhere('gateway_reference', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->latest()->paginate(25)->withQueryString();

        // Stats
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        $stats = [
            'today_revenue' => MobilePayment::completed()->whereDate('created_at', $today)->sum('amount'),
            'today_count' => MobilePayment::whereDate('created_at', $today)->count(),
            'month_revenue' => MobilePayment::completed()->where('created_at', '>=', $thisMonth)->sum('amount'),
            'month_count' => MobilePayment::where('created_at', '>=', $thisMonth)->count(),
            'total_revenue' => MobilePayment::completed()->sum('amount'),
            'pending_count' => MobilePayment::pending()->count(),
            'failed_count' => MobilePayment::where('status', 'failed')->where('created_at', '>=', $thisMonth)->count(),
        ];

        $operators = MobilePayment::selectRaw('DISTINCT operator')->pluck('operator')->filter();

        return view('admin.transactions.index', compact('transactions', 'stats', 'operators'));
    }

    public function show(MobilePayment $transaction)
    {
        $transaction->load('user');

        return view('admin.transactions.show', compact('transaction'));
    }
}
