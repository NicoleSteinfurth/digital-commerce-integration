<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/env.php';
\Stripe\Stripe::setApiKey($config['stripe']['secret_key']);

$productKey = (string) ($_GET['product'] ?? 'demo_product');
if (!isset($config['products'][$productKey])) {
    http_response_code(404);
    exit('Unknown product.');
}

$product = $config['products'][$productKey];

$session = \Stripe\Checkout\Session::create([
    'mode' => 'payment',
    'payment_method_types' => ['card', 'paypal'],
    'billing_address_collection' => 'required',
    'line_items' => [[
        'price' => $product['price_id'],
        'quantity' => 1,
    ]],
    'success_url' => $config['app']['base_url'] . '/success.html?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => $config['app']['base_url'] . '/cancel.html',
    'metadata' => [
        'product_key' => $productKey,
    ],
]);

header('Location: ' . $session->url, true, 303);
exit;
