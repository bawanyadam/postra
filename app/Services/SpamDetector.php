<?php

namespace App\Services;

class SpamDetector
{
    /**
     * Basic heuristics for obvious bot submissions.
     *
     * @param array<string,mixed> $payload
     */
    public function isSpam(array $payload): bool
    {
        if ($payload === []) {
            return false;
        }

        $flattened = $this->normalize($payload);
        $lc = strtolower($flattened);

        $score = 0;

        if (preg_match('/<\s*a\s|href=|<\/?[a-z0-9]+\s*>/', $lc)) {
            $score += 3;
        }

        $urlMatches = preg_match_all('/https?:\/\/[^\s]+/', $lc);
        if ($urlMatches !== false && $urlMatches >= 2) {
            $score += 2;
        }

        if (preg_match('/&#\d+;|%3c|%3e|&lt;|&gt;/', $lc)) {
            $score += 1;
        }

        if (strlen($lc) >= 2000) {
            $score += 1;
        }

        if (preg_match('/\b(casino|bet|jackpot|insurance|loan|crypto|blockchain|poker|slots)\b/', $lc)) {
            $score += 2;
        }

        return $score >= 4;
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function normalize(array $payload): string
    {
        $parts = [];
        foreach ($payload as $value) {
            if (is_scalar($value) || $value === null) {
                $parts[] = (string)$value;
                continue;
            }
            $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($encoded !== false) {
                $parts[] = $encoded;
            }
        }
        return implode(' ', array_filter($parts, static fn(string $item): bool => $item !== ''));
    }
}
