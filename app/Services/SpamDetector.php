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

        $randomTokenCount = 0;
        foreach ($this->extractStrings($payload) as $value) {
            if ($this->looksRandomToken($value)) {
                $randomTokenCount++;
            }
        }
        if ($randomTokenCount >= 3) {
            $score += 3;
        } elseif ($randomTokenCount === 2) {
            $score += 2;
        }

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

    /**
     * @param array<string,mixed> $payload
     * @return string[]
     */
    private function extractStrings(array $payload): array
    {
        $strings = [];
        foreach ($payload as $value) {
            if (is_string($value) || is_numeric($value) || $value === null) {
                $strings[] = trim((string)$value);
                continue;
            }
            if (is_array($value)) {
                $strings = array_merge($strings, $this->extractStrings($value));
            }
        }
        return array_filter($strings, static fn(string $item): bool => $item !== '');
    }

    private function looksRandomToken(string $value): bool
    {
        $value = trim($value);
        if ($value === '' || strlen($value) < 8) {
            return false;
        }
        if (preg_match('/\s/', $value)) {
            return false;
        }

        $upperCount = preg_match_all('/[A-Z]/', $value) ?: 0;
        $lowerCount = preg_match_all('/[a-z]/', $value) ?: 0;
        $digitCount = preg_match_all('/\d/', $value) ?: 0;
        $letterCount = $upperCount + $lowerCount;

        // Mix of upper/lower in atypical ratio (not simple Title Case)
        if ($letterCount >= 8 && $upperCount > 0 && $lowerCount > 0) {
            if (!preg_match('/^[A-Z][a-z]+$/', $value)) {
                $ratio = $upperCount / max(1, $letterCount);
                if ($ratio > 0.3 && $ratio < 0.7 && strlen($value) >= 10) {
                    return true;
                }
            }
        }

        // Mostly consonants (few vowels) indicates gibberish
        if ($letterCount >= 8) {
            $lettersOnly = preg_replace('/[^A-Za-z]/', '', $value);
            $lettersOnly = $lettersOnly ?? '';
            $lettersLen = strlen($lettersOnly);
            if ($lettersLen >= 8) {
                $vowelCount = preg_match_all('/[aeiou]/i', $lettersOnly) ?: 0;
                if ($vowelCount <= 1 || ($vowelCount / $lettersLen) < 0.2) {
                    return true;
                }
            }
        }

        // Alpha-numeric mix with many digits and letters together
        if ($digitCount >= 3 && $letterCount >= 5) {
            if (!preg_match('/^\d+$/', $value)) {
                return true;
            }
        }

        return false;
    }
}
