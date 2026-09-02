<?php

/**
 * Copy this file to config/env.php and replace placeholders locally.
 * config/env.php is intentionally excluded from Git.
 */
return [
    'app' => [
        'base_url' => 'https://example.test',
    ],

    'database' => [
        'host' => '127.0.0.1',
        'name' => 'demo_store',
        'charset' => 'utf8mb4',
        'user' => 'your_database_user',
        'password' => 'your_database_password',
    ],

    'stripe' => [
        'secret_key' => 'sk_test_your_key_here',
        'webhook_secret' => 'whsec_your_webhook_secret_here',
    ],

    'mail' => [
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'your_smtp_username',
        'password' => 'your_smtp_password',
        'from_email' => 'shop@example.com',
        'from_name' => 'Demo Store',
    ],

    'brevo' => [
        'api_key' => 'your_brevo_api_key',
    ],

    'products' => [
        'demo_product' => [
            'name' => 'Demo Digital Product',
            'price_id' => 'price_your_stripe_price_id',
            'file_path' => __DIR__ . '/../storage/products/demo-product.pdf',
            'expires_days' => 7,
            'max_downloads' => 3,
            'brevo_attribute' => 'BOUGHT_DEMO_PRODUCT',
            'brevo_list_id' => 0,
            'email_subject' => 'Your download is ready',
        ],
    ],
];
