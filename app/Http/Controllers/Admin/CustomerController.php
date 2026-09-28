<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCustomerRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\WalletEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — customer (end-user) management (original controller).
 * Staff users live in UserController; this covers app customers: list with
 * search/status filter, create/edit, detail with order history + wallet
 * ledger + manual top-up, guarded delete.
 */
class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::query()
            ->when($request->string('search'), function ($query, string $search) {
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%"));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'active');
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.customers.index', ['customers' => $customers]);
    }

    public function create(): View
    {
        return view('admin.customers.form', [
            'customer' => new Customer(),
            'method' => 'POST',
            'action' => route('admin.customers.store'),
        ]);
    }

    public function store(SaveCustomerRequest $request): RedirectResponse
    {
        $customer = Customer::create($request->validated());

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', "Customer '{$customer->name}' created.");
    }

    public function show(Customer $customer): View
    {
        $orders = Order::where('customer_id', $customer->id)
            ->orWhere(fn ($q) => $q->where('customer_phone', $customer->phone))
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $entries = WalletEntry::where('owner_type', 'customer')
            ->where(fn ($q) => $q->where('owner_ref', $customer->phone)
                ->when($customer->legacy_id, fn ($qq) => $qq->orWhere('owner_ref', $customer->legacy_id)))
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $balance = (clone $entries)->sum('amount');

        return view('admin.customers.show', compact('customer', 'orders', 'entries', 'balance'));
    }

    public function edit(Customer $customer): View
    {
        return view('admin.customers.form', [
            'customer' => $customer,
            'method' => 'PUT',
            'action' => route('admin.customers.update', $customer),
        ]);
    }

    public function update(SaveCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $customer->update($data);

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', "Customer '{$customer->name}' updated.");
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $orderCount = Order::where('customer_id', $customer->id)->count();

        if ($orderCount > 0) {
            return redirect()->route('admin.customers.index')->with(
                'error', "Cannot delete '{$customer->name}': {$orderCount} order(s) on record."
            );
        }

        $customer->delete();

        return redirect()->route('admin.customers.index')
            ->with('success', "Customer '{$customer->name}' deleted.");
    }

    /** Manual wallet top-up (adjustment): credit entry + note. */
    public function topup(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        WalletEntry::create([
            'owner_type' => 'customer',
            'owner_ref' => $customer->phone,
            'amount' => $validated['amount'],
            'kind' => 'adjustment',
            'method' => 'manual',
            'status' => 'success',
            'note' => $validated['note'] ?? 'Manual top-up by staff',
            'occurred_at' => now(),
        ]);

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', 'Wallet topped up.');
    }
}
