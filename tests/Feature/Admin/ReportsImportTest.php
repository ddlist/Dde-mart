<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart Admin — dashboard data, sales report, legacy importer (D10, original).
 */
class ReportsImportTest extends TestCase
{
    use RefreshDatabase;

    protected function superAdmin(): User
    {
        $role = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'is_super' => true],
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_dashboard_shows_real_numbers(): void
    {
        $admin = $this->superAdmin();
        Order::create([
            'customer_name' => 'Sara', 'payment_method' => 'cod',
            'subtotal' => 100, 'total' => 100, 'status' => Order::COMPLETED,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('100.00'); // MTD revenue
        $response->assertSee('Recent orders');
    }

    public function test_sales_report_filters_and_totals(): void
    {
        $admin = $this->superAdmin();
        Order::create(['customer_name' => 'A', 'payment_method' => 'cod', 'subtotal' => 100, 'total' => 90, 'discount' => 10, 'status' => Order::COMPLETED]);
        Order::create(['customer_name' => 'B', 'payment_method' => 'cod', 'subtotal' => 50, 'total' => 50, 'status' => Order::PLACED]);

        $response = $this->actingAs($admin)->get(route('admin.reports.sales', ['status' => 'completed']));

        $response->assertOk();
        $response->assertSee('A');
        $response->assertDontSee('>B<');
        $response->assertSee('90.00'); // net of filtered set
    }

    public function test_sales_csv_export(): void
    {
        $admin = $this->superAdmin();
        Order::create(['customer_name' => 'A', 'payment_method' => 'cod', 'subtotal' => 100, 'total' => 100, 'status' => Order::COMPLETED]);

        $response = $this->actingAs($admin)->get(route('admin.reports.salesExport'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Number,Date,Customer', $response->streamedContent());
        $this->assertStringContainsString(',A,', $response->streamedContent());
    }

    public function test_reports_gate_denies_unprivileged(): void
    {
        $role = Role::create(['name' => 'Limited', 'slug' => 'limited']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('admin.reports.sales'))->assertForbidden();
    }

    public function test_importer_dry_run_writes_nothing(): void
    {
        $this->artisan('ddemart:import', [
            '--file' => base_path('tests/Fixtures/legacy-sample.json'),
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertEquals(0, Section::count());
        $this->assertEquals(0, Product::count());
    }

    public function test_importer_imports_and_links_catalog(): void
    {
        $this->artisan('ddemart:import', [
            '--file' => base_path('tests/Fixtures/legacy-sample.json'),
        ])->assertSuccessful();

        $section = Section::where('legacy_id', 'sec001')->firstOrFail();
        $this->assertEquals('food', $section->service_type);
        // Remote image kept as-is (Images::url passes it through).
        $this->assertEquals('https://example.com/food.png', $section->image_path);
        $this->assertEquals('https://example.com/food.png', \App\Support\Images::url($section->image_path));

        $category = Category::where('legacy_id', 'cat001')->firstOrFail();
        $this->assertEquals($section->id, $category->section_id);
        $this->assertTrue($category->show_in_homepage);

        $this->assertEquals($section->id, Brand::where('legacy_id', 'br001')->firstOrFail()->section_id);

        $product = Product::where('legacy_id', 'pr001')->firstOrFail();
        $this->assertEquals($category->id, $product->category_id);
        $this->assertEquals(7.99, $product->sellingPrice());
        $this->assertCount(1, $product->addons); // blank row skipped
        $this->assertEquals('Extra cheese', $product->addons->first()->name);

        // Priceless product skipped (no price).
        $this->assertNull(Product::where('legacy_id', 'pr002')->first());

        $coupon = Coupon::where('legacy_id', 'cp001')->firstOrFail();
        $this->assertEquals('BIG20', $coupon->code);
        $this->assertEquals('percentage', $coupon->discount_type);
        $this->assertEquals(40.0, $coupon->calculateDiscount(200)); // 20% of 200

        // Re-run is idempotent (updates, no duplicates).
        $this->artisan('ddemart:import', [
            '--file' => base_path('tests/Fixtures/legacy-sample.json'),
        ])->assertSuccessful();

        $this->assertEquals(1, Section::count());
        $this->assertEquals(1, Product::where('legacy_id', 'pr001')->count());
    }

    public function test_importer_rejects_bad_file(): void
    {
        $this->artisan('ddemart:import', [])->assertFailed();
        $this->artisan('ddemart:import', ['--file' => 'nope.json'])->assertFailed();
    }
}
