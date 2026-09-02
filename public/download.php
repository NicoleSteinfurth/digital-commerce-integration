<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database;

$config = require __DIR__ . '/../config/env.php';
$token = (string) ($_GET['token'] ?? '');

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    http_response_code(404);
    exit('Download not found.');
}

$pdo = Database::connection();
$stmt = $pdo->prepare('SELECT * FROM download_tokens WHERE token = :token LIMIT 1');
$stmt->execute([':token' => $token]);
$download = $stmt->fetch();

if (!$download) {
    http_response_code(404);
    exit('Download not found.');
}

if ((int) $download['downloads_used'] >= (int) $download['max_downloads']) {
    http_response_code(403);
    exit('Download limit reached.');
}

if (new DateTimeImmutable($download['expires_at']) < new DateTimeImmutable()) {
    http_response_code(403);
    exit('Download link expired.');
}

$productKey = $download['product_key'];
if (!isset($config['products'][$productKey])) {
    http_response_code(500);
    exit('Product configuration missing.');
}

$filePath = $config['products'][$productKey]['file_path'];
if (!is_file($filePath)) {
    http_response_code(404);
    exit('Product file not found.');
}

$pdo->prepare('UPDATE download_tokens SET downloads_used = downloads_used + 1 WHERE id = :id')
    ->execute([':id' => $download['id']]);

header('Content-Type: ' . (mime_content_type($filePath) ?: 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
header('Content-Length: ' . filesize($filePath));
header('X-Content-Type-Options: nosniff');
readfile($filePath);
