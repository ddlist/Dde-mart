<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/*
 * DDE-Mart Admin — sales reports (original controller).
 * Server-computed from MySQL (legacy was a Firestore JS shell).
 * Same filtered query powers the screen and the CSV export.
 */
class ReportController extends Controller
{
    public function sales(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', 'string'],
        ]);

        $orders = $this->filteredQuery($filters)->orderByDesc('id')->paginate(20)->withQueryString();
        $summary = $this->summarize($filters);
        $daily = $this->daily($filters);

        return view('admin.reports.sales', [
            'orders' => $orders,
            'summary' => $summary,
            'daily' => $daily,
            'filters' => $filters,
            'statuses' => Order::STATUSES,
        ]);
    }

    public function salesExport(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', 'string'],
        ]);

        $filename = 'sales-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($filters) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Number', 'Date', 'Customer', 'Status', 'Payment', 'Subtotal', 'Discount', 'Delivery', 'Tax', 'Total']);

            $this->filteredQuery($filters)->orderBy('id')->chunk(500, function ($orders) use ($out) {
                foreach ($orders as $order) {
                    fputcsv($out, [
                        $order->number, $order->created_at->format('Y-m-d H:i'),
                        $order->customer_name, $order->status, $order->payment_method,
                        $order->subtotal, $order->discount, $order->delivery_charge,
                        $order->tax, $order->total,
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function filteredQuery(array $filters)
    {
        return Order::query()
            ->when(! empty($filters['from']), fn ($q) => $q->whereDate('created_at', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn ($q) => $q->whereDate('created_at', '<=', $filters['to']))
            ->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']));
    }

    protected function summarize(array $filters): array
    {
        $row = $this->filteredQuery($filters)->selectRaw(
            'COUNT(*) as orders, COALESCE(SUM(subtotal),0) as gross, COALESCE(SUM(discount),0) as discount,
             COALESCE(SUM(delivery_charge),0) as delivery, COALESCE(SUM(tax),0) as tax, COALESCE(SUM(total),0) as net'
        )->first();

        return [
            'orders' => (int) $row->orders,
            'gross' => (float) $row->gross,
            'discount' => (float) $row->discount,
            'delivery' => (float) $row->delivery,
            'tax' => (float) $row->tax,
            'net' => (float) $row->net,
        ];
    }

    protected function daily(array $filters): array
    {
        return $this->filteredQuery($filters)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as orders, COALESCE(SUM(total),0) as net')
            ->groupBy('day')
            ->orderByDesc('day')
            ->limit(30)
            ->get()
            ->map(fn ($r) => ['day' => $r->day, 'orders' => (int) $r->orders, 'net' => (float) $r->net])
            ->all();
    }
}
