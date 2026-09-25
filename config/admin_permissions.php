<?php

/*
 * DDE-Mart Admin — permission catalog (original config, not copied).
 * Inspired by: legacy checkbox matrix in role forms + `permission:<group>,<route>` usage.
 *
 * Each group maps to abilities. Stored permission key = "<group>.<ability>".
 * Groups for future modules (stores, orders, ...) are pre-declared so roles
 * created today stay valid as D5–D9 land. Only `dashboard` + `roles` are enforced yet.
 */
return [
    'dashboard' => [
        'label' => 'Dashboard',
        'abilities' => ['view' => 'View dashboard'],
    ],
    'roles' => [
        'label' => 'Roles & Permissions',
        'abilities' => [
            'view' => 'View roles',
            'create' => 'Create roles',
            'edit' => 'Edit roles',
            'delete' => 'Delete roles',
        ],
    ],
    'users' => [
        'label' => 'Users (D5)',
        'abilities' => [
            'view' => 'View users',
            'create' => 'Create users',
            'edit' => 'Edit users',
            'delete' => 'Delete users',
        ],
    ],
    'stores' => [
        'label' => 'Stores (D6)',
        'abilities' => [
            'view' => 'View stores',
            'create' => 'Create stores',
            'edit' => 'Edit stores',
            'delete' => 'Delete stores',
        ],
    ],
    'drivers' => [
        'label' => 'Drivers & verification',
        'abilities' => [
            'view' => 'View drivers',
            'create' => 'Create drivers',
            'edit' => 'Edit drivers & review documents',
            'delete' => 'Delete drivers',
        ],
    ],
    'owners' => [
        'label' => 'Owners directory',
        'abilities' => [
            'view' => 'View owners',
            'create' => 'Create owners',
            'edit' => 'Edit owners',
            'delete' => 'Delete owners',
        ],
    ],
    'orders' => [
        'label' => 'Orders (D7)',
        'abilities' => [
            'view' => 'View orders',
            'edit' => 'Update order status',
        ],
    ],
    'transport' => [
        'label' => 'Parcel, rental, rides',
        'abilities' => [
            'view' => 'View transport',
            'create' => 'Create transport records',
            'edit' => 'Manage transport',
            'delete' => 'Delete transport records',
        ],
    ],
    'catalog' => [
        'label' => 'Catalog (D6)',
        'abilities' => [
            'view' => 'View catalog',
            'create' => 'Create items',
            'edit' => 'Edit items',
            'delete' => 'Delete items',
        ],
    ],
    'promotions' => [
        'label' => 'Promotions (D8)',
        'abilities' => [
            'view' => 'View promotions',
            'create' => 'Create promotions',
            'edit' => 'Edit promotions',
            'delete' => 'Delete promotions',
        ],
    ],
    'finance' => [
        'label' => 'Finance (D8)',
        'abilities' => [
            'view' => 'View transactions',
            'create' => 'Create tax/currency/plan records',
            'edit' => 'Manage payouts',
            'delete' => 'Delete finance records',
        ],
    ],
    'content' => [
        'label' => 'Content (D9)',
        'abilities' => [
            'view' => 'View content',
            'create' => 'Create content',
            'edit' => 'Edit content',
            'delete' => 'Delete content',
        ],
    ],
    'settings' => [
        'label' => 'Settings (D5)',
        'abilities' => [
            'view' => 'View settings',
            'edit' => 'Edit settings',
        ],
    ],
    'reports' => [
        'label' => 'Reports (D10)',
        'abilities' => ['view' => 'View reports'],
    ],
];
