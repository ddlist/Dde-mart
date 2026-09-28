<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/*
 * DDE-Mart Admin — food order list, detail, and transitions (original controller).
 * Read-only except status moves via OrderStatus::transition. No destroy route:
 * financial records are cancelled, never deleted.
 */
class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $base = Order::where('type', 'food');

        $orders = (clone $base)->with(['section'])
            ->when($request->filled('search'), fn ($q) => $q
                ->where('number', 'like', '%'.$request->input('search').'%')
                ->orWhere('customer_name', 'like', '%'.$request->input('search').'%')
                ->orWhere('customer_phone', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        $kpis = [
            'Total' => (clone $base)->count(),
            'Placed' => (clone $base)->where('status', 'placed')->count(),
            'Accepted' => (clone $base)->where('status', 'accepted')->count(),
            'Completed' => (clone $base)->where('status', 'completed')->count(),
        ];

        return view('admin.orders.index', [
            'orders' => $orders,
            'statuses' => Order::STATUSES,
            'kpis' => $kpis,
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['items', 'history.changedBy', 'section']);

        $drivers = \App\Models\Driver::where('status', 'active')
            ->orderBy('name')->get(['id', 'name', 'phone', 'is_online']);

        return view('admin.orders.show', [
            'order' => $order,
            'allowed' => OrderStatus::allowedFor($order),
            'statuses' => Order::STATUSES,
            'drivers' => $drivers,
        ]);
    }

    /** Manual driver assignment (mirrors the ride flow). */
    public function assign(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate(['driver_id' => ['required', 'integer', 'exists:drivers,id']]);
        $order->update(['driver_id' => $validated['driver_id']]);
        $order->history()->create([
            'from_status' => $order->status,
            'to_status' => $order->status,
            'note' => 'Driver assigned by staff',
        ]);

        app(\App\Services\WorkforceNotifier::class)->orderAssigned($order->fresh(), (int) $validated['driver_id']);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Driver assigned.');
    }

    /** Preparation time in minutes (kitchen display aid). */
    public function prepTime(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate(['estimated_prep_minutes' => ['required', 'integer', 'min:1', 'max:480']]);
        $order->update(['estimated_prep_minutes' => $validated['estimated_prep_minutes']]);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Preparation time saved.');
    }

    public function transition(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            OrderStatus::transition($order, $validated['to'], $request->user(), $validated['note'] ?? null);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('admin.orders.show', $order)->with('error', $e->getMessage());
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', "Order moved to ".Order::STATUSES[$validated['to']].'.');
    }
}
