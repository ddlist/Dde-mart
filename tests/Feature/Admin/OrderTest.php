<?php

namespace Tests\Feature\Admin;

use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Tests\TestCase;

/*
 * DDE-Mart Admin — food order pipeline tests (D7, original).
 */
class OrderTest extends TestCase
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

    protected function makeOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'customer_name' => 'Test Customer',
            'customer_phone' => '03001234567',
            'payment_method' => 'cod',
            'subtotal' => 100,
            'total' => 100,
        ], $overrides));
    }

    public function test_order_gets_human_readable_number(): void
    {
        $order = $this->makeOrder();

        $this->assertMatchesRegularExpression('/^DDE-\d{6}-\d{5}$/', $order->fresh()->number);
    }

    public function test_valid_transition_records_history_and_fires_event(): void
    {
        Event::fake([OrderStatusChanged::class]);
        $admin = $this->superAdmin();
        $order = $this->makeOrder();

        $moved = OrderStatus::transition($order, Order::ACCEPTED, $admin, 'Stock confirmed');

        $this->assertEquals(Order::ACCEPTED, $moved->status);
        $this->assertCount(1, $moved->history);
        $this->assertEquals('Stock confirmed', $moved->history->first()->note);
        $this->assertEquals($admin->id, $moved->history->first()->changed_by);
        Event::assertDispatched(OrderStatusChanged::class);
    }

    public function test_illegal_transition_is_rejected(): void
    {
        $order = $this->makeOrder(['status' => Order::COMPLETED]);

        $this->expectException(InvalidArgumentException::class);

        OrderStatus::transition($order, Order::PLACED);
    }

    public function test_unknown_status_is_rejected(): void
    {
        $order = $this->makeOrder();

        $this->expectException(InvalidArgumentException::class);

        OrderStatus::transition($order, 'teleported');
    }

    public function test_guest_cannot_access_orders(): void
    {
        $this->get(route('admin.orders.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_orders_grant_gets_forbidden(): void
    {
        $role = Role::create(['name' => 'Limited', 'slug' => 'limited']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('admin.orders.index'))->assertForbidden();
    }

    public function test_index_filters_by_status_and_search(): void
    {
        $admin = $this->superAdmin();
        $this->makeOrder(['customer_name' => 'Sara Khan', 'status' => Order::PLACED]);
        $this->makeOrder(['customer_name' => 'Ali Raza', 'status' => Order::COMPLETED]);

        $byStatus = $this->actingAs($admin)->get(route('admin.orders.index', ['status' => 'completed']));
        $byStatus->assertOk()->assertSee('Ali Raza')->assertDontSee('Sara Khan');

        $bySearch = $this->actingAs($admin)->get(route('admin.orders.index', ['search' => 'Sara']));
        $bySearch->assertOk()->assertSee('Sara Khan')->assertDontSee('Ali Raza');
    }

    public function test_admin_can_move_order_with_note(): void
    {
        $admin = $this->superAdmin();
        $order = $this->makeOrder();

        $response = $this->actingAs($admin)->post(route('admin.orders.transition', $order), [
            'to' => Order::ACCEPTED,
            'note' => 'Confirmed by phone',
        ]);

        $response->assertRedirect(route('admin.orders.show', $order));
        $this->assertEquals(Order::ACCEPTED, $order->fresh()->status);

        $detail = $this->actingAs($admin)->get(route('admin.orders.show', $order));
        $detail->assertOk()->assertSee('Confirmed by phone')->assertSee('Accepted');
    }

    public function test_admin_cannot_make_illegal_move(): void
    {
        $admin = $this->superAdmin();
        $order = $this->makeOrder(['status' => Order::PLACED]);

        $response = $this->actingAs($admin)->post(route('admin.orders.transition', $order), [
            'to' => Order::COMPLETED, // must go through accepted first
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(Order::PLACED, $order->fresh()->status);
    }
}
