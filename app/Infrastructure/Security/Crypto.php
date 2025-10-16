<?php

namespace App\Infrastructure\Security;

use App\Support\Env;

class Crypto
{
    public static function encrypt(string $plaintext): string
    {
        // Ensure env is loaded before reading key
        Env::load();
        $keyB64 = Env::get('POSTRA_ENCRYPTION_KEY_BASE64', '');
        if (extension_loaded('sodium') && $keyB64) {
            $key = base64_decode($keyB64, true);
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $cipher = sodium_crypto_secretbox($plaintext, $nonce, $key);
            return base64_encode($nonce . $cipher);
        }
        return base64_encode($plaintext); // fallback (not secure)
    }

    public static function decrypt(string $blob): string
    {
        // Ensure env is loaded before reading key
        Env::load();
        $keyB64 = Env::get('POSTRA_ENCRYPTION_KEY_BASE64', '');
        if (extension_loaded('sodium') && $keyB64) {
            $data = base64_decode($blob, true);
            $key = base64_decode($keyB64, true);
            if ($data !== false && $key !== false && strlen($key) === SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
                $nonce = substr($data, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
                $cipher = substr($data, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
                $plain = sodium_crypto_secretbox_open($cipher, $nonce, $key);
                if ($plain !== false) {
                    return $plain;
                }
            }
            // Backward-compatibility: if decrypt fails, treat as base64 plaintext
            $fallback = base64_decode($blob, true);
            if ($fallback !== false) {
                return $fallback;
            }
            throw new \RuntimeException('Decryption failed');
        }
        return base64_decode($blob, true) ?: '';
    }
}
