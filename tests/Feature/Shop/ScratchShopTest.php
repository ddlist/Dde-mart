<?php

namespace Tests\Feature\Shop;

use App\Models\Customer;
use App\Models\ParcelOrder;
use App\Models\ParcelWeight;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScratchShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_track(): void
    {
        $customer = Customer::create(['name' => 'Sara', 'phone' => '03001234567', 'password' => 'secret123']);
        $this->post(route('shop.login.attempt'), ['phone' => '03001234567', 'password' => 'secret123']);
        $weight = ParcelWeight::create(['title' => '5 KG', 'delivery_charge' => 50]);

        $b = $this->post(route('shop.parcel.book'), [
            'sender_name' => 'Ali', 'sender_phone' => '03001234567',
            'sender_address' => 'A', 'receiver_name' => 'S',
            'receiver_phone' => '03009998877', 'receiver_address' => 'B',
            'weight_id' => $weight->id, 'distance_km' => 1,
        ]);
        dump('book: '.$b->getStatusCode());

        $order = ParcelOrder::first();
        dump('order: '.($order ? $order->id.' ['.$order->sender_phone.']' : 'NONE'));
        dump('authed: '.(auth('customer')->check() ? '['.auth('customer')->user()->phone.']' : 'GUEST'));

        $t = $this->get(route('shop.parcel.track', $order));
        dump('track: '.$t->getStatusCode());
        dump('url: '.route('shop.parcel.track', $order));
        if ($t->exception) {
            $prev = $t->exception->getPrevious();
            dump(get_class($t->exception).': '.$t->exception->getMessage());
            dump('file: '.$t->exception->getFile().':'.$t->exception->getLine());
            if ($prev) {
                dump('prev: '.get_class($prev).': '.$prev->getMessage());
            }
        }
        $this->assertTrue(true);
    }
}
