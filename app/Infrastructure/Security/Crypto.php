<?php

namespace App\Infrastructure\Security;

use App\Support\Env;

class Crypto
{
    public static function encrypt(string $plaintext): string
    {
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
        $keyB64 = Env::get('POSTRA_ENCRYPTION_KEY_BASE64', '');
        if (extension_loaded('sodium') && $keyB64) {
            $data = base64_decode($blob, true);
            $key = base64_decode($keyB64, true);
            $nonce = substr($data, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $cipher = substr($data, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $plain = sodium_crypto_secretbox_open($cipher, $nonce, $key);
            if ($plain === false) {
                throw new \RuntimeException('Decryption failed');
            }
            return $plain;
        }
        return base64_decode($blob, true) ?: '';
    }
}

