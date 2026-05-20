<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Category;
use App\Models\Transaction;
use Inertia\Inertia;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Fetch recent transactions (replacing the merged StockIn/StockOut)
        $recent_transactions = Transaction::with('item')
            ->latest()
            ->limit(8)
            ->get()
            ->map(function($transaction) {
                // Ensure the 'type' matches your frontend expectations (In/Out)
                $transaction->setAttribute('type', ucfirst($transaction->type));
                return $transaction;
            });

        // 2. Prepare Data
        $data = [
            'stats' => [
                'total_items'      => Item::count(),
                'low_stock_count'  => Item::whereRaw('quantity <= min_stock')->count(),
                'total_categories' => Category::count(),
                'recent_updates'   => Transaction::whereDate('created_at', today())->count(),
            ],
            'top_stock_items' => Item::orderBy('quantity', 'desc')
                ->limit(5)
                ->get(),
            'low_stock_items' => Item::whereRaw('quantity <= min_stock')
                ->with(['unit', 'categories'])
                ->get(),
            'recent_transactions' => $recent_transactions,
        ];

        if ($request->wantsJson()) {
            return $data;
        }

        return Inertia::render('Dashboard', $data);
    }
}