<?php

/*
 * DDE-Mart Admin — ops settings defaults (original config).
 * DB values (via Setting model) override these. Secrets NEVER live here.
 */
return [
    'defaults' => [
        // General
        'site_name' => 'DDE-Mart',
        'support_email' => 'support@dde-mart.local',
        'support_phone' => '',
        // Orders
        'order_auto_cancel_minutes' => '30',
        'driver_accept_seconds' => '60',
        'default_prep_minutes' => '20',
        // Payouts
        'min_withdrawal' => '100',
        // Geo
        'distance_unit' => 'km',
        'default_radius_km' => '5',
        'parcel_per_km' => '2',
        // Platform
        'business_model' => 'commission',
    ],
    'groups' => [
        'general' => ['label' => 'General', 'keys' => ['site_name', 'support_email', 'support_phone']],
        'orders' => ['label' => 'Orders', 'keys' => ['order_auto_cancel_minutes', 'driver_accept_seconds', 'default_prep_minutes']],
        'payouts' => ['label' => 'Payouts', 'keys' => ['min_withdrawal']],
        'geo' => ['label' => 'Geo', 'keys' => ['distance_unit', 'default_radius_km', 'parcel_per_km']],
        'platform' => ['label' => 'Platform', 'keys' => ['business_model']],
    ],
];
