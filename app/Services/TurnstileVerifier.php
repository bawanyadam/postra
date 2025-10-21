<?php

namespace App\Services;

use App\Support\Env;

class TurnstileVerifier
{
    /**
     * Verify Cloudflare Turnstile token.
     * Returns [bool $ok, array $response]
     */
    public static function verify(?string $token, ?string $remoteIp = null): array
    {
        Env::load();
        $secret = Env::get('TURNSTILE_SECRET_KEY', '') ?: '';
        if ($secret === '') {
            // Not configured: treat as pass to avoid blocking installs without keys
            return [true, ['skipped' => true, 'reason' => 'missing-secret']];
        }
        $token = trim((string)$token);
        if ($token === '') {
            return [false, ['success' => false, 'error-codes' => ['missing-token']]];
        }
        $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $data = [
            'secret' => $secret,
            'response' => $token,
        ];
        if ($remoteIp) {
            $data['remoteip'] = $remoteIp;
        }

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'timeout' => 5,
                'content' => http_build_query($data),
            ],
        ];
        $context = stream_context_create($options);
        $resp = @file_get_contents($url, false, $context);
        if ($resp === false) {
            return [false, ['success' => false, 'error-codes' => ['network-error']]];
        }
        $json = json_decode($resp, true);
        $ok = is_array($json) && !empty($json['success']);
        return [$ok, is_array($json) ? $json : ['success' => false, 'error-codes' => ['invalid-json']]];
    }
}

