<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Models\Language;
use App\Models\NotificationTemplate;
use App\Models\Page;
use Illuminate\Database\Seeder;

/*
 * DDE-Mart Admin — content defaults (original seeder).
 * Order push templates, a welcome email, core pages, and base languages.
 * Idempotent via updateOrCreate on unique keys.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $pushes = [
            'order.placed' => ['Your order :order_number is placed', 'Hi :customer, we received your order of :total.'],
            'order.accepted' => ['Order :order_number accepted', 'Good news :customer — your order is accepted.'],
            'order.preparing' => ['Order :order_number is being prepared', 'Your food is on the flame, :customer.'],
            'order.shipped' => ['Order :order_number is on the way', 'Rider picked up your order, :customer.'],
            'order.completed' => ['Order :order_number delivered', 'Enjoy your meal, :customer!'],
            'order.cancelled' => ['Order :order_number cancelled', 'Sorry :customer, your order was cancelled.'],
            'order.rejected' => ['Order :order_number not accepted', 'Sorry :customer, the store could not accept your order.'],
        ];

        foreach ($pushes as $key => [$subject, $body]) {
            NotificationTemplate::updateOrCreate(
                ['key' => $key],
                ['audience' => 'customer', 'subject' => $subject, 'body' => $body, 'is_active' => true],
            );
        }

        EmailTemplate::updateOrCreate(
            ['key' => 'order.placed'],
            [
                'subject' => 'Your DDE-Mart order :order_number is placed',
                'body' => "Hi :customer,\n\nThanks for ordering :total with DDE-Mart.\n\nTeam DDE-Mart",
                'send_to_admin' => false,
                'is_active' => true,
            ],
        );

        foreach ([
            ['Terms & Conditions', 'terms', ' platform rules go here.'],
            ['Privacy Policy', 'privacy', ' privacy commitments go here.'],
            ['About Us', 'about', ' story goes here.'],
        ] as [$name, $slug, $tail]) {
            Page::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'body' => "DDE-Mart {$name}{$tail}", 'is_active' => true],
            );
        }

        Language::updateOrCreate(['code' => 'en'], ['name' => 'English', 'is_default' => true, 'is_active' => true]);
        Language::updateOrCreate(['code' => 'ur'], ['name' => 'Urdu', 'is_active' => true]);

        foreach ([
            ['homepage_hero', 'Homepage hero', 'Big headline + CTA for the storefront.'],
            ['homepage_promos', 'Homepage promos', 'Promo strip under the hero.'],
            ['footer_about', 'Footer about', 'Short about blurb for the footer.'],
            ['footer_contact', 'Footer contact', 'Support email + phone for the footer.'],
        ] as [$key, $title, $body]) {
            \App\Models\ContentBlock::updateOrCreate(['key' => $key], ['title' => $title, 'body' => $body, 'is_active' => true]);
        }
    }
}
