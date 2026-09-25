<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PayoutRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — DashboardController (original).
 * Inspired by: legacy HomeController (cookie-driven vertical shells) — reimplemented
 * as ONE dashboard backed by MySQL aggregates. Replaces the D1 placeholders (D10).
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $today = now()->startOfDay();
        $month = now()->startOfMonth();

        $revenueMtd = (float) Order::where('status', Order::COMPLETED)
            ->where('created_at', '>=', $month)
            ->sum('total');

        $stats = [
            ['label' => 'Staff Users', 'value' => User::count(), 'hint' => User::count().' accounts'],
            ['label' => 'Active Products', 'value' => Product::where('is_active', true)->count(), 'hint' => Product::count().' total'],
            ['label' => 'Orders Today', 'value' => Order::where('created_at', '>=', $today)->count(), 'hint' => Order::count().' all time'],
            ['label' => 'Revenue (MTD)', 'value' => number_format($revenueMtd, 2), 'hint' => 'completed orders'],
        ];

        $statusBreakdown = Order::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        $recentOrders = Order::orderByDesc('id')->limit(5)->get();

        $lowStock = Product::where('is_active', true)
            ->where('quantity', '<=', 5)
            ->orderBy('quantity')
            ->limit(5)
            ->get();

        $pendingPayouts = [
            'count' => PayoutRequest::where('status', 'pending')->count(),
            'amount' => (float) PayoutRequest::where('status', 'pending')->sum('amount'),
        ];

        return view('admin.dashboard', compact(
            'stats', 'statusBreakdown', 'recentOrders', 'lowStock', 'pendingPayouts'
        ));
    }
}
