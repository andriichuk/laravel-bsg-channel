<?php

declare(strict_types=1);

namespace Andriichuk\BsgChannel\Services;

use Andriichuk\Bsg\Contracts\BsgClientInterface;
use Andriichuk\Bsg\Responses\SmsStatusResponse;

final readonly class SmsStatusService
{
    public function __construct(private BsgClientInterface $client) {}

    public function byId(string|int $id): SmsStatusResponse
    {
        return $this->client->getSmsStatus($id);
    }

    public function byReference(string $reference): SmsStatusResponse
    {
        return $this->client->getSmsStatusByReference($reference);
    }
}
