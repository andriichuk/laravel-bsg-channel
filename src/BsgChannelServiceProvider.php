<?php

declare(strict_types=1);

namespace Andriichuk\BsgChannel;

use Andriichuk\BsgChannel\Channels\SmsChannel;
use Illuminate\Support\Facades\Notification;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class BsgChannelServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('laravel-bsg-channel');
    }

    public function packageRegistered(): void
    {
        $this->app->register(BsgServiceProvider::class);
    }

    public function packageBooted(): void
    {
        Notification::extend('bsg-sms', static function ($app): SmsChannel {
            return $app->make(SmsChannel::class);
        });
    }
}
