<?php

declare(strict_types=1);

namespace App;

use PHPMailer\PHPMailer\PHPMailer;

final class Mailer
{
    public function sendDownload(
        array $config,
        string $toEmail,
        string $productName,
        string $downloadUrl,
        ?string $invoicePdfPath = null
    ): void {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $config['mail']['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['mail']['username'];
        $mail->Password = $config['mail']['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int) $config['mail']['port'];
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($config['mail']['from_email'], $config['mail']['from_name']);
        $mail->addAddress($toEmail);

        if ($invoicePdfPath && is_file($invoicePdfPath)) {
            $mail->addAttachment($invoicePdfPath, basename($invoicePdfPath));
        }

        $safeProduct = htmlspecialchars($productName, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8');

        $mail->isHTML(true);
        $mail->Subject = 'Your download: ' . $productName;
        $mail->Body = "<p>Thank you for purchasing <strong>{$safeProduct}</strong>.</p>"
            . "<p><a href=\"{$safeUrl}\">Open your secure download</a></p>";
        $mail->AltBody = "Thank you for purchasing {$productName}.\n\nDownload: {$downloadUrl}";

        $mail->send();
    }
}
