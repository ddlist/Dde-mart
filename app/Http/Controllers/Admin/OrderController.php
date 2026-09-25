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
        $orders = Order::with(['section'])
            ->where('type', 'food')
            ->when($request->filled('search'), fn ($q) => $q
                ->where('number', 'like', '%'.$request->input('search').'%')
                ->orWhere('customer_name', 'like', '%'.$request->input('search').'%')
                ->orWhere('customer_phone', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'statuses' => Order::STATUSES,
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['items', 'history.changedBy', 'section']);

        return view('admin.orders.show', [
            'order' => $order,
            'allowed' => OrderStatus::allowedFor($order),
            'statuses' => Order::STATUSES,
        ]);
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
