<?php

declare(strict_types=1);

namespace Andriichuk\BsgSmsChannel;

use RuntimeException;

final class BsgApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $response
     */
    public static function fromResponse(array $response): self
    {
        $error = (string) ($response['error'] ?? 'unknown');
        $description = (string) ($response['errorDescription'] ?? $response['description'] ?? $response['message'] ?? 'Unknown error');

        return new self("BSG API error {$error}: {$description}");
    }
}
