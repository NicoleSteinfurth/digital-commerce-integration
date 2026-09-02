<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\BrevoService;
use App\Database;
use App\FulfillmentService;
use App\InvoiceGenerator;
use App\Mailer;

$config = require __DIR__ . '/../config/env.php';
$payload = file_get_contents('php://input') ?: '';
$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $signature,
        $config['stripe']['webhook_secret']
    );
} catch (Throwable $e) {
    http_response_code(400);
    exit('Invalid webhook.');
}

if ($event->type !== 'checkout.session.completed') {
    http_response_code(200);
    exit('ignored');
}

try {
    $service = new FulfillmentService(
        Database::connection(),
        new Mailer(),
        new InvoiceGenerator(),
        new BrevoService((string) ($config['brevo']['api_key'] ?? '')),
        $config
    );

    $service->processCheckout($event->data->object);
    http_response_code(200);
    echo 'ok';
} catch (Throwable $e) {
    error_log('Webhook processing failed: ' . $e->getMessage());
    http_response_code(500);
    echo 'Internal server error.';
}
