<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ParcelOrder;
use App\Models\PayoutRequest;
use App\Models\Product;
use App\Models\ProviderBooking;
use App\Models\RentalOrder;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — DashboardController (original).
 * One dashboard with a vertical switcher (all/food/parcel/rental/ride/
 * service): scoped revenue, orders, status breakdown, 14-day sales bars,
 * recent rows and top lists. Replaces the D1 placeholders (D10) and the
 * legacy per-vertical shells with a single MySQL-backed page.
 */
class DashboardController extends Controller
{
    public const VERTICALS = ['all', 'food', 'parcel', 'rental', 'ride', 'service'];

    public function index(): View
    {
        $vertical = request()->input('vertical', 'all');
        if (! in_array($vertical, self::VERTICALS, true)) {
            $vertical = 'all';
        }

        $today = now()->startOfDay();
        $month = now()->startOfMonth();

        $orders = $this->scoped($vertical, null);
        $revenueMtd = (float) (clone $orders)
            ->where('created_at', '>=', $month)
            ->whereIn('status', ['completed', 'delivered'])
            ->sum('total');

        $stats = [
            ['label' => 'Revenue (MTD)', 'value' => number_format($revenueMtd, 2), 'hint' => 'completed orders'],
            ['label' => 'Orders Today', 'value' => (clone $orders)->where('created_at', '>=', $today)->count(), 'hint' => (clone $orders)->count().' all time'],
            ['label' => 'Staff Users', 'value' => User::count(), 'hint' => User::count().' accounts'],
            ['label' => 'Active Products', 'value' => Product::where('is_active', true)->count(), 'hint' => Product::count().' total'],
        ];

        $statusBreakdown = (clone $orders)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        $recentOrders = (clone $orders)->orderByDesc('id')->limit(5)->get();

        $sales = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            $sales[] = [
                'label' => $day->format('d M'),
                'total' => (float) (clone $orders)
                    ->where('created_at', '>=', $day)
                    ->where('created_at', '<', $day->copy()->addDay())
                    ->whereIn('status', ['completed', 'delivered'])
                    ->sum('total'),
            ];
        }
        $salesMax = max(1, max(array_column($sales, 'total')));

        $topStores = $vertical === 'food' || $vertical === 'all'
            ? Order::selectRaw('stores.name as name, COUNT(orders.id) as orders, SUM(orders.total) as revenue')
                ->join('stores', 'stores.id', '=', 'orders.vendor_id')
                ->groupBy('stores.id', 'stores.name')
                ->orderByDesc('revenue')
                ->limit(5)
                ->get()
            : collect();

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
            'stats', 'statusBreakdown', 'recentOrders', 'lowStock',
            'pendingPayouts', 'vertical', 'sales', 'salesMax', 'topStores'
        ));
    }

    /** Base query for a vertical (union shape: status/total/created_at). */
    protected function scoped(string $vertical, ?string $status): Builder
    {
        return match ($vertical) {
            'parcel' => ParcelOrder::query(),
            'rental' => RentalOrder::query(),
            'ride' => Ride::query(),
            'service' => ProviderBooking::query(),
            'food' => Order::where('type', 'food'),
            default => Order::query(),
        };
    }
}
