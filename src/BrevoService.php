<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

final class BrevoService
{
    public function __construct(private readonly string $apiKey)
    {
    }

    public function syncBuyer(string $email, string $productName, string $attribute, int $listId = 0): void
    {
        if ($this->apiKey === '') {
            return;
        }

        $payload = [
            'email' => $email,
            'attributes' => [
                $attribute => true,
                'LAST_PRODUCT' => $productName,
            ],
            'updateEnabled' => true,
        ];

        if ($listId > 0) {
            $payload['listIds'] = [$listId];
        }

        $ch = curl_init('https://api.brevo.com/v3/contacts');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'accept: application/json',
                'content-type: application/json',
                'api-key: ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $status < 200 || $status >= 300) {
            throw new RuntimeException(sprintf('Brevo request failed (HTTP %d): %s', $status, $error));
        }
    }
}
