<?php

namespace Tests\Feature\Admin;

use App\Models\Driver;
use App\Models\Order;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart Admin — reports tests (original): sales filters/print and the
 * earnings report with per-store commission math + CSV.
 */
class ReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function staff(): User
    {
        $role = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'is_super' => true],
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    protected function seedOrders(): array
    {
        $store = Store::create([
            'name' => 'Tasty Bites', 'slug' => 'tasty-bites', 'status' => 'approved',
            'commission_type' => 'percentage', 'commission_value' => 10,
        ]);
        $driver = Driver::create(['name' => 'Ali', 'phone' => '03001', 'status' => 'approved']);

        Order::create([
            'number' => 'pending', 'customer_name' => 'Sara', 'vendor_id' => $store->id,
            'driver_id' => $driver->id, 'subtotal' => 1000, 'discount' => 0,
            'delivery_charge' => 150, 'tip' => 50, 'tax' => 0, 'total' => 1200,
            'status' => Order::COMPLETED,
        ]);
        // Non-completed order: excluded from earnings, included in sales.
        Order::create([
            'number' => 'pending', 'customer_name' => 'Omar', 'vendor_id' => $store->id,
            'subtotal' => 500, 'discount' => 0, 'delivery_charge' => 0,
            'tip' => 0, 'tax' => 0, 'total' => 500,
            'status' => Order::PLACED,
        ]);

        return [$store, $driver];
    }

    public function test_sales_filters_and_print(): void
    {
        $admin = $this->staff();
        [$store, $driver] = $this->seedOrders();

        $this->actingAs($admin)
            ->get(route('admin.reports.sales', ['vendor_id' => $store->id]))
            ->assertOk()
            ->assertSee('Sara');

        // Driver filter narrows to the delivered order only.
        $this->actingAs($admin)
            ->get(route('admin.reports.sales', ['driver_id' => $driver->id]))
            ->assertOk()
            ->assertSee('Sara')
            ->assertDontSee('Omar');

        $this->actingAs($admin)
            ->get(route('admin.reports.salesPrint', ['status' => Order::COMPLETED]))
            ->assertOk()
            ->assertSee('Sales Report')
            ->assertSee('Sara');

        $this->actingAs($admin)
            ->get(route('admin.reports.salesExport', ['status' => Order::COMPLETED]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_earnings_math_and_export(): void
    {
        $admin = $this->staff();
        $this->seedOrders();

        // 10% of 1000 gross = 100 cut, 900 net; driver: 150 fees + 50 tips.
        $response = $this->actingAs($admin)->get(route('admin.reports.earnings'));
        $response->assertOk()
            ->assertSee('Tasty Bites')
            ->assertSee('900.00')
            ->assertSee('Ali')
            ->assertSee('200.00');

        $csv = $this->actingAs($admin)
            ->get(route('admin.reports.earningsExport'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('VENDOR PAYOUTS', $csv);
        $this->assertStringContainsString('Tasty Bites', $csv);
        $this->assertStringContainsString('DRIVER EARNINGS', $csv);
    }
}
