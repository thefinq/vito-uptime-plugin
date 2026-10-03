<?php

namespace App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services;

final readonly class CheckResult
{
    public function __construct(
        public bool $ok,
        public ?int $statusCode,
        public int $responseMs,
        public ?string $error = null,
    ) {}

    public static function success(int $statusCode, int $responseMs): self
    {
        return new self(true, $statusCode, $responseMs);
    }

    public static function failure(string $error, ?int $statusCode, int $responseMs): self
    {
        return new self(false, $statusCode, $responseMs, $error);
    }
}
