<?php

namespace App\Support;

class Env
{
    private static array $cache = [];

    public static function load(string $path = __DIR__ . '/../../.env'): void
    {
        if (!file_exists($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                [$key, $value] = $parts;
                $key = trim($key);
                $value = trim($value);
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }
                $_ENV[$key] = $value;
                putenv($key . '=' . $value);
                self::$cache[$key] = $value;
            }
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return $_ENV[$key] ?? getenv($key) ?: self::$cache[$key] ?? $default;
    }
}

