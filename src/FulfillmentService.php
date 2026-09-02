<?php

declare(strict_types=1);

namespace App;

use DateTimeImmutable;
use PDO;
use Throwable;

final class FulfillmentService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly Mailer $mailer,
        private readonly InvoiceGenerator $invoices,
        private readonly BrevoService $brevo,
        private readonly array $config
    ) {
    }

    public function processCheckout(object $session): void
    {
        $productKey = $session->metadata->product_key ?? null;
        if (!$productKey || !isset($this->config['products'][$productKey])) {
            throw new \InvalidArgumentException('Unknown product.');
        }

        $product = $this->config['products'][$productKey];
        $email = $session->customer_details->email ?? $session->customer_email ?? null;
        if (!$email) {
            throw new \InvalidArgumentException('Customer email is missing.');
        }

        // Idempotency: Stripe may deliver the same event more than once.
        $existing = $this->pdo->prepare(
            'SELECT id FROM orders WHERE stripe_session_id = :session_id LIMIT 1'
        );
        $existing->execute([':session_id' => $session->id]);
        if ($existing->fetch()) {
            return;
        }

        $now = new DateTimeImmutable();
        $token = bin2hex(random_bytes(32));
        $expiresAt = $now->modify('+' . (int) $product['expires_days'] . ' days');
        $downloadUrl = $this->config['app']['base_url'] . '/download.php?token=' . urlencode($token);

        $this->pdo->beginTransaction();
        try {
            $order = $this->pdo->prepare(
                'INSERT INTO orders (stripe_session_id, product_key, product_name, customer_email, amount_total, currency, purchased_at) '
                . 'VALUES (:session_id, :product_key, :product_name, :email, :amount_total, :currency, :purchased_at)'
            );
            $order->execute([
                ':session_id' => $session->id,
                ':product_key' => $productKey,
                ':product_name' => $product['name'],
                ':email' => $email,
                ':amount_total' => (int) ($session->amount_total ?? 0),
                ':currency' => (string) ($session->currency ?? 'eur'),
                ':purchased_at' => $now->format('Y-m-d H:i:s'),
            ]);

            $invoiceNumber = $this->nextInvoiceNumber();
            $invoicePath = $this->invoices->generate([
                'customer_email' => $email,
                'product_name' => $product['name'],
                'amount_total' => (int) ($session->amount_total ?? 0),
                'currency' => (string) ($session->currency ?? 'eur'),
            ], $invoiceNumber);

            $orderId = (int) $this->pdo->lastInsertId();
            $update = $this->pdo->prepare(
                'UPDATE orders SET invoice_number = :invoice_number, invoice_pdf_path = :invoice_path WHERE id = :id'
            );
            $update->execute([
                ':invoice_number' => $invoiceNumber,
                ':invoice_path' => $invoicePath,
                ':id' => $orderId,
            ]);

            $download = $this->pdo->prepare(
                'INSERT INTO download_tokens (product_key, customer_email, stripe_session_id, token, downloads_used, max_downloads, expires_at) '
                . 'VALUES (:product_key, :email, :session_id, :token, 0, :max_downloads, :expires_at)'
            );
            $download->execute([
                ':product_key' => $productKey,
                ':email' => $email,
                ':session_id' => $session->id,
                ':token' => $token,
                ':max_downloads' => (int) $product['max_downloads'],
                ':expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            ]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        // Downstream integrations are intentionally outside the database transaction.
        try {
            $this->brevo->syncBuyer(
                $email,
                $product['name'],
                $product['brevo_attribute'],
                (int) $product['brevo_list_id']
            );
        } catch (Throwable $e) {
            error_log('CRM sync failed: ' . $e->getMessage());
        }

        try {
            $this->mailer->sendDownload($this->config, $email, $product['name'], $downloadUrl, $invoicePath);
            $this->logEmail($email, $productKey, 'sent', null);
        } catch (Throwable $e) {
            $this->logEmail($email, $productKey, 'failed', $e->getMessage());
        }
    }

    private function nextInvoiceNumber(): string
    {
        $year = date('Y');
        $stmt = $this->pdo->prepare(
            'SELECT invoice_number FROM orders WHERE invoice_number LIKE :prefix ORDER BY invoice_number DESC LIMIT 1'
        );
        $stmt->execute([':prefix' => $year . '-%']);
        $last = $stmt->fetchColumn();
        $next = $last ? ((int) substr((string) $last, 5)) + 1 : 1;

        return $year . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    private function logEmail(string $email, string $productKey, string $status, ?string $error): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO email_logs (customer_email, product_key, status, error_message) '
            . 'VALUES (:email, :product_key, :status, :error)'
        );
        $stmt->execute([
            ':email' => $email,
            ':product_key' => $productKey,
            ':status' => $status,
            ':error' => $error,
        ]);
    }
}
