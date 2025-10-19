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

        $strings = $this->extractStrings($payload);
        $fieldStrings = $this->extractFieldStrings($payload);

        $randomTokenCount = 0;
        foreach ($strings as $value) {
            if ($this->looksRandomToken($value)) {
                $randomTokenCount++;
            }
        }
        if ($randomTokenCount >= 3) {
            $score += 4;
        } elseif ($randomTokenCount === 2) {
            $score += 3;
        } elseif ($randomTokenCount === 1) {
            $score += 1;
        }

        $gibberishNameCount = 0;
        foreach ($fieldStrings as $key => $value) {
            if ($this->isLikelyNameKey($key) && $this->looksRandomToken($value)) {
                $gibberishNameCount++;
            }
        }
        if ($gibberishNameCount >= 1) {
            $score += 3;
        }

        if ($this->hasMatchingNameFields($fieldStrings)) {
            $score += 4;
        } elseif ($this->hasDuplicateTextValues($fieldStrings)) {
            $score += 4;
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

    /**
     * @param array<string,mixed> $payload
     * @param string $prefix
     * @return array<string,string>
     */
    private function extractFieldStrings(array $payload, string $prefix = ''): array
    {
        $strings = [];
        foreach ($payload as $key => $value) {
            $keyString = is_string($key) ? $key : (string)$key;
            if ($this->shouldSkipKey($keyString)) {
                continue;
            }
            $path = $prefix === '' ? $keyString : $prefix . '.' . $keyString;

            if (is_string($value) || is_numeric($value) || $value === null) {
                $stringValue = trim((string)$value);
                if ($stringValue !== '') {
                    $strings[$path] = $stringValue;
                }
                continue;
            }
            if (is_array($value)) {
                $strings += $this->extractFieldStrings($value, $path);
            }
        }
        return $strings;
    }

    private function shouldSkipKey(string $key): bool
    {
        $normalized = strtolower($key);
        if (str_starts_with($normalized, '_')) {
            return true;
        }
        if (str_starts_with($normalized, 'g-recaptcha')) {
            return true;
        }
        if (str_starts_with($normalized, 'hcaptcha')) {
            return true;
        }
        if (str_starts_with($normalized, '_postra')) {
            return true;
        }
        return $normalized === 'submit';
    }

    /**
     * @param array<string,string> $fields
     */
    private function hasDuplicateTextValues(array $fields): bool
    {
        $seen = [];
        foreach ($fields as $key => $value) {
            $normalized = strtolower(preg_replace('/\s+/', ' ', $value) ?? '');
            if ($normalized === '') {
                continue;
            }
            $compact = preg_replace('/\s+/', '', $normalized) ?? '';
            if (strlen($compact) < 8) {
                continue;
            }
            if (!array_key_exists($normalized, $seen)) {
                $seen[$normalized] = $key;
                continue;
            }
            if ($seen[$normalized] !== $key) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<string,string> $fields
     */
    private function hasMatchingNameFields(array $fields): bool
    {
        $firstValues = [];
        $lastValues = [];

        foreach ($fields as $key => $value) {
            if ($this->isLikelyFirstNameKey($key)) {
                $firstValues[] = $value;
            } elseif ($this->isLikelyLastNameKey($key)) {
                $lastValues[] = $value;
            }
        }

        if ($firstValues === [] || $lastValues === []) {
            return false;
        }

        foreach ($firstValues as $first) {
            foreach ($lastValues as $last) {
                $trimmedFirst = strtolower(trim($first));
                $trimmedLast = strtolower(trim($last));
                if ($trimmedFirst === '' || $trimmedLast === '') {
                    continue;
                }
                if ($trimmedFirst === $trimmedLast) {
                    return true;
                }
            }
        }

        return false;
    }

    private function normalizeKey(string $key): string
    {
        $normalized = strtolower($key);
        $normalized = preg_replace('/[^a-z0-9]+/', '', $normalized) ?? $normalized;
        return $normalized;
    }

    private function isLikelyNameKey(string $key): bool
    {
        $normalized = $this->normalizeKey($key);
        if ($normalized === '') {
            return false;
        }
        if (str_contains($normalized, 'company')) {
            return false;
        }
        if (str_contains($normalized, 'username')) {
            return false;
        }
        return str_contains($normalized, 'name');
    }

    private function isLikelyFirstNameKey(string $key): bool
    {
        $normalized = $this->normalizeKey($key);
        return $normalized === 'first'
            || str_contains($normalized, 'firstname')
            || str_contains($normalized, 'fname')
            || str_contains($normalized, 'givenname');
    }

    private function isLikelyLastNameKey(string $key): bool
    {
        $normalized = $this->normalizeKey($key);
        return $normalized === 'last'
            || str_contains($normalized, 'lastname')
            || str_contains($normalized, 'lname')
            || str_contains($normalized, 'surname')
            || str_contains($normalized, 'familyname');
    }
}
