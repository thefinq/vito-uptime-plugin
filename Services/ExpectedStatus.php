<?php

namespace App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services;

/**
 * Parses the "expected status" field: "200", "200,204", "200-299", "2xx", or a mix
 * separated by commas.
 */
class ExpectedStatus
{
    public static function isValid(string $spec): bool
    {
        if (trim($spec) === '') {
            return false;
        }

        foreach (self::parts($spec) as $part) {
            if (! preg_match('/^(\d{3}|\d{3}-\d{3}|[1-5]xx)$/i', $part)) {
                return false;
            }
        }

        return true;
    }

    public static function matches(string $spec, int $code): bool
    {
        foreach (self::parts($spec) as $part) {
            if (preg_match('/^(\d{3})$/', $part, $m) && (int) $m[1] === $code) {
                return true;
            }
            if (preg_match('/^(\d{3})-(\d{3})$/', $part, $m) && $code >= (int) $m[1] && $code <= (int) $m[2]) {
                return true;
            }
            if (preg_match('/^([1-5])xx$/i', $part, $m) && intdiv($code, 100) === (int) $m[1]) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private static function parts(string $spec): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $spec)), fn (string $p): bool => $p !== ''));
    }
}
