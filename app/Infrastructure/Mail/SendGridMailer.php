<?php

namespace App\Infrastructure\Mail;

use App\Support\Env;

class SendGridMailer
{
    public function send(string $apiKey, string $toEmail, string $subject, string $html, string $text, ?string $replyTo = null): bool
    {
        Env::load();
        $fromEmail = Env::get('POSTRA_FROM_EMAIL', 'submission@postra.to') ?: 'submission@postra.to';
        $fromName  = Env::get('POSTRA_FROM_NAME', 'Postra') ?: 'Postra';
        $payload = [
            'personalizations' => [[ 'to' => [[ 'email' => $toEmail ]] ]],
            'from' => [ 'email' => $fromEmail, 'name' => $fromName ],
            'subject' => $subject,
            'content' => [
                [ 'type' => 'text/plain', 'value' => $text ],
                [ 'type' => 'text/html', 'value' => $html ],
            ],
        ];
        if ($replyTo) {
            $payload['reply_to'] = ['email' => $replyTo];
        }
        $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 10,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($resp === false) {
            error_log('SendGrid error: ' . curl_error($ch));
        }
        curl_close($ch);
        return $code >= 200 && $code < 300;
    }
}
