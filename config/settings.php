<?php

/*
 * DDE-Mart Admin — ops settings defaults + form schema (original config).
 * DB values (via Setting model) override these. Secrets NEVER live here.
 *
 * Types: text | number | bool | select | file:image | file:audio | display.
 * File keys store a public-disk path; display keys are computed, not stored.
 */
return [
    'defaults' => [
        // General
        'site_name' => 'DDE-Mart',
        'support_email' => 'support@dde-mart.local',
        'support_phone' => '',
        // Branding (public-disk paths)
        'app_logo' => '',
        'menu_placeholder' => '',
        'provider_logo' => '',
        'worker_logo' => '',
        // Contact
        'contact_address' => '',
        'contact_email' => '',
        'contact_phone' => '',
        'default_country' => '',
        // Maps
        'maps_key' => '',
        'maps_app' => 'google',
        'maps_redirect' => 'google',
        'driver_location_interval' => '30',
        'single_order_receive' => '0',
        // Orders
        'order_auto_cancel_minutes' => '30',
        'driver_accept_seconds' => '60',
        'default_prep_minutes' => '20',
        // Auto-dispatch (scheduled DispatchOrders command)
        'dispatch_auto' => '0',
        'dispatch_radius_km' => '5',
        'dispatch_location_stale_minutes' => '30',
        // Payouts
        'min_withdrawal' => '100',
        'min_deposit' => '10',
        'min_deposit_owner' => '10',
        // Delivery (floor applied to parcel quotes)
        'delivery_min' => '0',
        // Geo
        'distance_unit' => 'km',
        'default_radius_km' => '5',
        'parcel_per_km' => '2',
        // Store + provider feature flags
        'story_enable' => '0',
        'story_duration' => '30',
        'auto_approve_vendor' => '0',
        'auto_approve_provider' => '0',
        'ads_enable' => '0',
        'self_delivery_enable' => '0',
        // Ringtone (public-disk path)
        'ringtone' => '',
        // Email (host/user/pass stay in .env)
        'mail_from_name' => 'DDE-Mart',
        // Versions
        'web_version' => '1.0.0',
        'website_url' => '',
        'store_url' => '',
        'provider_url' => '',
        // Document verification gates
        'verify_store' => '1',
        'verify_driver' => '1',
        'verify_owner' => '1',
        // Platform
        'business_model' => 'commission',
        'admin_commission_enable' => '0',
        'admin_commission_type' => 'percentage',
        'admin_commission_value' => '0',
        // Mobile apps (editable in admin; read by GET /api/v1/app-config)
        'apps_maintenance' => '0',
        'maint_customer' => '0',
        'maint_driver' => '0',
        'maint_vendor' => '0',
        'maint_provider' => '0',
        'maint_worker' => '0',
        'min_app_customer' => '1.0.0',
        'min_app_driver' => '1.0.0',
        'min_app_vendor' => '1.0.0',
        'min_app_provider' => '1.0.0',
        'min_app_worker' => '1.0.0',
    ],
    'groups' => [
        'general' => ['label' => 'General', 'keys' => ['site_name', 'support_email', 'support_phone']],
        'branding' => ['label' => 'Branding', 'keys' => ['app_logo', 'menu_placeholder', 'provider_logo', 'worker_logo']],
        'contact' => ['label' => 'Contact', 'keys' => ['contact_address', 'contact_email', 'contact_phone', 'default_country']],
        'maps' => ['label' => 'Maps', 'keys' => ['maps_key', 'maps_app', 'maps_redirect', 'driver_location_interval', 'single_order_receive']],
        'orders' => ['label' => 'Orders', 'keys' => ['order_auto_cancel_minutes', 'driver_accept_seconds', 'default_prep_minutes']],
        'dispatch' => ['label' => 'Auto-dispatch', 'keys' => ['dispatch_auto', 'dispatch_radius_km', 'dispatch_location_stale_minutes']],
        'payouts' => ['label' => 'Payouts', 'keys' => ['min_withdrawal', 'min_deposit', 'min_deposit_owner']],
        'delivery' => ['label' => 'Delivery', 'keys' => ['delivery_min']],
        'features' => ['label' => 'Feature flags', 'keys' => ['story_enable', 'story_duration', 'auto_approve_vendor', 'auto_approve_provider', 'ads_enable', 'self_delivery_enable']],
        'ringtone' => ['label' => 'Ringtone', 'keys' => ['ringtone']],
        'email' => ['label' => 'Email', 'keys' => ['mail_from_name']],
        'versions' => ['label' => 'Versions & links', 'keys' => ['web_version', 'website_url', 'store_url', 'provider_url']],
        'verification' => ['label' => 'Document verification', 'keys' => ['verify_store', 'verify_driver', 'verify_owner']],
        'geo' => ['label' => 'Geo', 'keys' => ['distance_unit', 'default_radius_km', 'parcel_per_km']],
        'platform' => ['label' => 'Platform', 'keys' => ['business_model', 'admin_commission_enable', 'admin_commission_type', 'admin_commission_value']],
        'apps' => ['label' => 'Mobile apps', 'keys' => ['apps_maintenance', 'maint_customer', 'maint_driver', 'maint_vendor', 'maint_provider', 'maint_worker', 'min_app_customer', 'min_app_driver', 'min_app_vendor', 'min_app_provider', 'min_app_worker']],
        'integrations' => ['label' => 'Integrations status', 'keys' => []],
    ],
    'types' => [
        'app_logo' => ['type' => 'file', 'kind' => 'image', 'help' => 'Main app logo (PNG/JPG, max 2MB).'],
        'menu_placeholder' => ['type' => 'file', 'kind' => 'image', 'help' => 'Placeholder while menus load.'],
        'provider_logo' => ['type' => 'file', 'kind' => 'image', 'help' => 'Provider app logo.'],
        'worker_logo' => ['type' => 'file', 'kind' => 'image', 'help' => 'Worker app logo.'],
        'ringtone' => ['type' => 'file', 'kind' => 'audio', 'help' => 'New-order alert sound (MP3/OGG).'],
        'contact_address' => ['type' => 'text', 'help' => 'Shown on contact pages.'],
        'default_country' => ['type' => 'text', 'help' => 'Default country for phone inputs, e.g. PK.'],
        'maps_key' => ['type' => 'text', 'help' => 'Google Maps key. Prefer .env MAPS_KEY for production.'],
        'maps_app' => ['type' => 'select', 'options' => ['google' => 'Google', 'osm' => 'OpenStreetMap']],
        'maps_redirect' => ['type' => 'select', 'options' => ['google' => 'Google', 'googleGo' => 'Google Go', 'waze' => 'Waze', 'mapswithme' => 'Maps.me', 'yandexNavi' => 'Yandex Navi', 'yandexMaps' => 'Yandex Maps', 'inappmap' => 'In-app map']],
        'driver_location_interval' => ['type' => 'number', 'help' => 'Seconds between driver position pings.'],
        'single_order_receive' => ['type' => 'bool', 'help' => 'Drivers hold one active order at a time.'],
        'story_enable' => ['type' => 'bool', 'help' => 'Stores may upload stories.'],
        'story_duration' => ['type' => 'number', 'help' => 'Story video seconds.'],
        'auto_approve_vendor' => ['type' => 'bool', 'help' => 'Applies where self-serve vendor onboarding exists.'],
        'auto_approve_provider' => ['type' => 'bool', 'help' => 'Applies where self-serve provider onboarding exists.'],
        'ads_enable' => ['type' => 'bool', 'help' => 'Paid advertisements feature.'],
        'self_delivery_enable' => ['type' => 'bool', 'help' => 'Stores may deliver with own riders.'],
        'mail_from_name' => ['type' => 'text', 'help' => 'Sender name. Host/user/pass stay in .env.'],
        'verify_store' => ['type' => 'bool', 'help' => 'Require document verification for stores.'],
        'verify_driver' => ['type' => 'bool', 'help' => 'Auto-approves driver documents on submit when off.'],
        'verify_owner' => ['type' => 'bool', 'help' => 'Require document verification for owners.'],
        'order_auto_cancel_minutes' => ['type' => 'number'],
        'driver_accept_seconds' => ['type' => 'number'],
        'default_prep_minutes' => ['type' => 'number'],
        'dispatch_auto' => ['type' => 'bool'],
        'dispatch_radius_km' => ['type' => 'number'],
        'dispatch_location_stale_minutes' => ['type' => 'number'],
        'min_withdrawal' => ['type' => 'number', 'help' => 'Enforced on every payout request.'],
        'min_deposit' => ['type' => 'number', 'help' => 'Minimum wallet top-up.'],
        'min_deposit_owner' => ['type' => 'number', 'help' => 'Minimum owner wallet top-up.'],
        'delivery_min' => ['type' => 'number', 'help' => 'Floor for parcel delivery quotes.'],
        'distance_unit' => ['type' => 'select', 'options' => ['km' => 'Kilometers', 'miles' => 'Miles']],
        'default_radius_km' => ['type' => 'number'],
        'parcel_per_km' => ['type' => 'number'],
        'business_model' => ['type' => 'select', 'options' => ['commission' => 'Commission', 'subscription' => 'Subscription']],
        'admin_commission_enable' => ['type' => 'bool'],
        'admin_commission_type' => ['type' => 'select', 'options' => ['percentage' => 'Percentage', 'fixed' => 'Fixed amount']],
        'admin_commission_value' => ['type' => 'number'],
        'apps_maintenance' => ['type' => 'bool', 'help' => 'Global kill switch for all apps.'],
        'maint_customer' => ['type' => 'bool'],
        'maint_driver' => ['type' => 'bool'],
        'maint_vendor' => ['type' => 'bool'],
        'maint_provider' => ['type' => 'bool'],
        'maint_worker' => ['type' => 'bool'],
    ],
];
