<?php

declare(strict_types=1);

namespace App;

use Dompdf\Dompdf;
use Dompdf\Options;

final class InvoiceGenerator
{
    public function generate(array $order, string $invoiceNumber): string
    {
        $directory = __DIR__ . '/../storage/invoices';
        if (!is_dir($directory)) {
            mkdir($directory, 0750, true);
        }

        $path = $directory . '/invoice-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $invoiceNumber) . '.pdf';
        $amount = number_format(((int) $order['amount_total']) / 100, 2, '.', '');

        $html = sprintf(
            '<!doctype html><html><body><h1>Invoice %s</h1><p>Customer: %s</p><p>Product: %s</p><p>Total: %s %s</p></body></html>',
            self::e($invoiceNumber),
            self::e((string) $order['customer_email']),
            self::e((string) $order['product_name']),
            self::e($amount),
            self::e(strtoupper((string) $order['currency']))
        );

        $options = new Options();
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();
        file_put_contents($path, $dompdf->output());

        return $path;
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
