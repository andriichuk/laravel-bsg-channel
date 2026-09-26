<?php

declare(strict_types=1);

namespace Andriichuk\BsgSmsChannel;

use Illuminate\Support\Facades\Notification;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class BsgSmsChannelServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-bsg-sms-channel');
    }

    public function packageRegistered(): void
    {
        $this->app->register(BsgServiceProvider::class);
    }

    public function packageBooted(): void
    {
        Notification::extend('bsg', static function ($app): BsgChannel {
            return $app->make(BsgChannel::class);
        });
    }
}
