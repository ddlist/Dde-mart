<?php

namespace Tests\Feature\Shop;

use App\Models\Customer;
use App\Models\ParcelOrder;
use App\Models\ParcelWeight;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScratchParcelTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_parcel_track(): void
    {
        $customer = Customer::create(['name' => 'Sara', 'phone' => '03001234567', 'password' => 'secret123']);
        $this->post(route('shop.login.attempt'), ['phone' => '03001234567', 'password' => 'secret123']);
        
        $weight = ParcelWeight::create(['title' => '5 KG', 'delivery_charge' => 50]);

        $response = $this->post(route('shop.parcel.book'), [
            'sender_name' => 'Ali', 
            'sender_phone' => '03001234567',
            'sender_address' => 'A street',
            'receiver_name' => 'Sara', 
            'receiver_phone' => '03009998877',
            'receiver_address' => 'B street',
            'weight_id' => $weight->id, 
            'distance_km' => 3.5,
        ]);
        
        $order = \App\Models\ParcelOrder::firstOrFail();
        dump('Order ID: ' . $order->id);
        dump('Order sender_phone: ' . $order->sender_phone);
        dump('Customer phone: ' . $order->sender_phone);
        
        $response = $this->get(route('shop.parcel.track', $order));
        dump('Track status: ' . $response->getStatusCode());
        if ($response->getStatusCode() !== 200) {
            dump('Content: ' . $response->getContent());
        }
        $this->assertTrue(true);
    }
}