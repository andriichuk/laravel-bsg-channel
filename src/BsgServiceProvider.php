<?php

declare(strict_types=1);

namespace Andriichuk\BsgSmsChannel;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

final class BsgServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(BsgClient::class, static function (): BsgClient {
            return new BsgClient(
                (string) config('services.bsg.api_key'),
                (string) config('services.bsg.from'),
            );
        });
    }

    public function provides(): array
    {
        return [BsgClient::class];
    }
}
