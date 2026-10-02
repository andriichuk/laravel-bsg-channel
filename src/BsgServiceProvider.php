<?php

declare(strict_types=1);

namespace Andriichuk\BsgChannel;

use Andriichuk\Bsg\BsgClient;
use Andriichuk\Bsg\Contracts\BsgClientInterface;
use Andriichuk\BsgChannel\Services\SmsStatusService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

final class BsgServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(BsgClient::class, static function (): BsgClient {
            return new BsgClient(
                (string) config('services.bsg.api_key'),
            );
        });

        $this->app->alias(BsgClient::class, BsgClientInterface::class);
        $this->app->singleton(SmsStatusService::class);
    }

    public function provides(): array
    {
        return [BsgClient::class, BsgClientInterface::class, SmsStatusService::class];
    }
}
