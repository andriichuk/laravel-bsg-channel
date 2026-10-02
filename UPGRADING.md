# Upgrading from `laravel-bsg-sms-channel`

The package is now channel-agnostic so that SMS and future BSG transports such as Viber can coexist.

Update the Composer package name:

```bash
composer remove andriichuk/laravel-bsg-sms-channel
composer require andriichuk/laravel-bsg-channel
```

Update these application symbols:

| Before | After |
| --- | --- |
| `Andriichuk\BsgSmsChannel\BsgChannel` | `Andriichuk\BsgChannel\Channels\SmsChannel` |
| `Andriichuk\BsgSmsChannel\Sms` | `Andriichuk\BsgChannel\Messages\Sms` |
| `toBsg()` | `toBsgSms()` |
| `routeNotificationForBsg()` | `routeNotificationForBsgSms()` |
| `bsg` channel driver | `bsg-sms` channel driver |

The low-level `BsgClient`, API exception, request objects, and response objects now live in `andriichuk/bsg-php-sdk` under the `Andriichuk\Bsg` namespace.

The configuration keys under `services.bsg` remain `api_key`, `from`, and `log_response`.
