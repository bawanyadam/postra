<?php

namespace App\Support;

class Id
{
    // Simple ULID generator (time-based, Crockford base32)
    private const CROCKFORD = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public static function ulid(?int $timeMs = null): string
    {
        $timeMs = $timeMs ?? (int) floor(microtime(true) * 1000);
        $time = self::encodeInt($timeMs, 10);
        // 80 bits randomness -> 16 chars base32 (padding-free for ULID)
        // Use 10 random bytes to avoid base_convert limitations and leading-zero loss
        $rand = random_bytes(10); // 80 bits
        $bits = '';
        for ($i = 0, $len = strlen($rand); $i < $len; $i++) {
            $bits .= str_pad(decbin(ord($rand[$i])), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        for ($i = 0; $i < 80; $i += 5) {
            $chunk = substr($bits, $i, 5);
            $idx = bindec($chunk);
            $out .= self::CROCKFORD[$idx];
        }
        return $time . $out;
    }

    private static function encodeInt(int $value, int $length): string
    {
        $out = '';
        for ($i = $length - 1; $i >= 0; $i--) {
            $pow = 1;
            for ($k = 0; $k < $i; $k++) $pow *= 32;
            $idx = intdiv($value, $pow) % 32;
            $out .= self::CROCKFORD[$idx];
        }
        return $out;
    }
}
