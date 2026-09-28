{{-- DDE-Mart Admin — sales print view (original). Minimal chrome, print CSS; browser Print → PDF. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sales Report — {{ now()->format('d M Y H:i') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; color: #111; margin: 24px; font-size: 12px; }
        h1 { font-size: 20px; margin: 0; }
        p.meta { color: #555; margin: 4px 0 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
        td.num, th.num { text-align: right; }
        .summary span { display: inline-block; margin-right: 16px; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <h1>Sales Report</h1>
    <p class="meta">Generated {{ now()->format('d M Y H:i') }}
        @if (! empty($filters['from']) || ! empty($filters['to'])) · {{ $filters['from'] ?? '…' }} → {{ $filters['to'] ?? '…' }}@endif
        @if (! empty($filters['status'])) · status: {{ $filters['status'] }}@endif
        @if (! empty($filters['vendor_id'])) · vendor: {{ $filters['vendor_id'] }}@endif
        @if (! empty($filters['driver_id'])) · driver: {{ $filters['driver_id'] }}@endif
    </p>
    <p class="summary">
        <span><strong>Orders:</strong> {{ $summary['orders'] }}</span>
        <span><strong>Gross:</strong> {{ number_format($summary['gross'], 2) }}</span>
        <span><strong>Discount:</strong> {{ number_format($summary['discount'], 2) }}</span>
        <span><strong>Delivery:</strong> {{ number_format($summary['delivery'], 2) }}</span>
        <span><strong>Tax:</strong> {{ number_format($summary['tax'], 2) }}</span>
        <span><strong>Net:</strong> {{ number_format($summary['net'], 2) }}</span>
    </p>
    <table>
        <thead>
            <tr><th>Order</th><th>Date</th><th>Customer</th><th>Status</th><th>Payment</th><th class="num">Subtotal</th><th class="num">Discount</th><th class="num">Delivery</th><th class="num">Tax</th><th class="num">Total</th></tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td>{{ $order->number }}</td>
                    <td>{{ $order->created_at->format('d M Y') }}</td>
                    <td>{{ $order->customer_name }}</td>
                    <td>{{ $order->statusLabel() }}</td>
                    <td>{{ $order->payment_method }}</td>
                    <td class="num">{{ number_format($order->subtotal, 2) }}</td>
                    <td class="num">{{ number_format($order->discount, 2) }}</td>
                    <td class="num">{{ number_format($order->delivery_charge, 2) }}</td>
                    <td class="num">{{ number_format($order->tax, 2) }}</td>
                    <td class="num">{{ number_format($order->total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="10">No orders match.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="no-print"><button onclick="window.print()">Print / Save PDF</button></p>
</body>
</html>
