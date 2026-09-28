<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/*
 * DDE-Mart Admin — sales + earnings reports (original controller).
 * Server-computed from MySQL (legacy was a Firestore JS shell).
 * Same filtered query powers the screen, the print view, and the CSV export.
 * Earnings use per-store commission (commission_type/value) with the global
 * admin_commission_* settings as fallback; the driver leg reports delivery
 * fees (delivery_charge + tip) on completed orders.
 */
class ReportController extends Controller
{
    public function sales(Request $request): View
    {
        $filters = $this->validateSalesFilters($request);

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
        $filters = $this->validateSalesFilters($request);

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

    /** Print-friendly sales report (browser Print → PDF). No pagination. */
    public function salesPrint(Request $request): View
    {
        $filters = $this->validateSalesFilters($request);

        return view('admin.reports.sales-print', [
            'orders' => $this->filteredQuery($filters)->orderBy('id')->limit(500)->get(),
            'summary' => $this->summarize($filters),
            'filters' => $filters,
        ]);
    }

    /** Earnings: vendor payouts (commission cut) + driver delivery fees. */
    public function earnings(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'vendor_id' => ['nullable', 'integer'],
            'driver_id' => ['nullable', 'integer'],
        ]);

        return view('admin.reports.earnings', [
            'vendors' => $this->vendorEarnings($filters),
            'drivers' => $this->driverEarnings($filters),
            'filters' => $filters,
        ]);
    }

    public function earningsExport(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'vendor_id' => ['nullable', 'integer'],
            'driver_id' => ['nullable', 'integer'],
        ]);

        $filename = 'earnings-'.now()->format('Ymd-His').'.csv';
        $vendors = $this->vendorEarnings($filters);
        $drivers = $this->driverEarnings($filters);

        return response()->streamDownload(function () use ($vendors, $drivers) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['VENDOR PAYOUTS']);
            fputcsv($out, ['Vendor', 'Orders', 'Gross', 'Platform cut', 'Net payout']);
            foreach ($vendors as $row) {
                fputcsv($out, [$row['name'], $row['orders'], $row['gross'], $row['cut'], $row['net']]);
            }
            fputcsv($out, []);
            fputcsv($out, ['DRIVER EARNINGS']);
            fputcsv($out, ['Driver', 'Deliveries', 'Delivery fees', 'Tips', 'Total']);
            foreach ($drivers as $row) {
                fputcsv($out, [$row['name'], $row['deliveries'], $row['fees'], $row['tips'], $row['total']]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function validateSalesFilters(Request $request): array
    {
        return $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', 'string'],
            'vendor_id' => ['nullable', 'integer'],
            'driver_id' => ['nullable', 'integer'],
        ]);
    }

    protected function filteredQuery(array $filters)
    {
        return Order::query()
            ->when(! empty($filters['from']), fn ($q) => $q->whereDate('created_at', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn ($q) => $q->whereDate('created_at', '<=', $filters['to']))
            ->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['vendor_id']), fn ($q) => $q->where('vendor_id', $filters['vendor_id']))
            ->when(! empty($filters['driver_id']), fn ($q) => $q->where('driver_id', $filters['driver_id']));
    }

    /** Completed orders grouped by vendor with commission cut applied. */
    protected function vendorEarnings(array $filters): array
    {
        $rows = Order::query()
            ->where('status', Order::COMPLETED)
            ->when(! empty($filters['from']), fn ($q) => $q->whereDate('created_at', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn ($q) => $q->whereDate('created_at', '<=', $filters['to']))
            ->when(! empty($filters['vendor_id']), fn ($q) => $q->where('vendor_id', $filters['vendor_id']))
            ->selectRaw('vendor_id, COUNT(*) as orders, COALESCE(SUM(subtotal),0) as gross')
            ->groupBy('vendor_id')
            ->get();

        $stores = Store::whereIn('id', $rows->pluck('vendor_id')->filter()->all())
            ->get()->keyBy('id');

        return $rows->map(function ($row) use ($stores) {
            $store = $stores->get($row->vendor_id);
            $gross = (float) $row->gross;
            $cut = $this->commissionCut($store, $gross);

            return [
                'name' => $store?->name ?? ('Vendor #'.$row->vendor_id),
                'orders' => (int) $row->orders,
                'gross' => $gross,
                'cut' => $cut,
                'net' => round($gross - $cut, 2),
            ];
        })->sortByDesc('gross')->values()->all();
    }

    /** Completed orders grouped by driver: delivery fees + tips. */
    protected function driverEarnings(array $filters): array
    {
        $rows = Order::query()
            ->where('status', Order::COMPLETED)
            ->whereNotNull('driver_id')
            ->when(! empty($filters['from']), fn ($q) => $q->whereDate('created_at', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn ($q) => $q->whereDate('created_at', '<=', $filters['to']))
            ->when(! empty($filters['driver_id']), fn ($q) => $q->where('driver_id', $filters['driver_id']))
            ->selectRaw('driver_id, COUNT(*) as deliveries, COALESCE(SUM(delivery_charge),0) as fees, COALESCE(SUM(tip),0) as tips')
            ->groupBy('driver_id')
            ->get();

        $drivers = Driver::whereIn('id', $rows->pluck('driver_id')->all())
            ->get()->keyBy('id');

        return $rows->map(function ($row) use ($drivers) {
            $fees = (float) $row->fees;
            $tips = (float) $row->tips;

            return [
                'name' => $drivers->get($row->driver_id)?->name ?? ('Driver #'.$row->driver_id),
                'deliveries' => (int) $row->deliveries,
                'fees' => $fees,
                'tips' => $tips,
                'total' => round($fees + $tips, 2),
            ];
        })->sortByDesc('total')->values()->all();
    }

    /** Platform cut for a gross amount: per-store rate, else global default. */
    protected function commissionCut(?Store $store, float $gross): float
    {
        $type = $store?->commission_type
            ?? (Setting::bool('admin_commission_enable') ? Setting::get('admin_commission_type', 'percentage') : null);
        $value = (float) ($store?->commission_value ?? Setting::get('admin_commission_value', 0));

        if (! $type || $value <= 0) {
            return 0.0;
        }

        return $type === 'percentage'
            ? round($gross * $value / 100, 2)
            : round(min($value, $gross), 2);
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
