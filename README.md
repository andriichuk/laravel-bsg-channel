# BSG World SMS Notification Channel for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/andriichuk/laravel-bsg-sms-channel.svg?style=flat-square)](https://packagist.org/packages/andriichuk/laravel-bsg-sms-channel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/andriichuk/laravel-bsg-sms-channel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/andriichuk/laravel-bsg-sms-channel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/andriichuk/laravel-bsg-sms-channel/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/andriichuk/laravel-bsg-sms-channel/actions?query=workflow%3A%22Fix+PHP+code+style+issues%22+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/andriichuk/laravel-bsg-sms-channel.svg?style=flat-square)](https://packagist.org/packages/andriichuk/laravel-bsg-sms-channel)

Send SMS notifications through the [BSG World REST API](https://bsg.world/developers/rest-api/sending-sms) using Laravel's notification system.

```php
$user->notify(new Invitation());
```

## Installation

**Requirements:** PHP 8.3+ and Laravel 11.x, 12.x, or 13.5+.

```bash
composer require andriichuk/laravel-bsg-sms-channel
```

The service provider is auto-discovered by Laravel.

## Configuration

Add your BSG API key and registered sender name to `config/services.php`:

```php
return [
    // ...

    'bsg' => [
        'api_key' => env('BSG_SMS_API_KEY'),
        'from' => env('BSG_SMS_FROM'),
        'log_response' => env('BSG_SMS_LOG_RESPONSE', false),
    ],
];
```

Then add the corresponding environment variables:

```dotenv
BSG_SMS_API_KEY="live_your_api_key"
BSG_SMS_FROM="YourSender"
BSG_SMS_LOG_RESPONSE=false
```

BSG sends the API key in the `X-API-KEY` request header. The sender name must be registered in your BSG account and may contain up to 14 characters.

## Usage

### Notifiable model

Add `routeNotificationForBsg` to the notifiable model and return the recipient's full international phone number:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public function routeNotificationForBsg(): string
    {
        return $this->phone; // e.g. +380991112233
    }
}
```

### Notification class

Add `BsgChannel` to `via()` and return an `Sms` instance from `toBsg()`:

```php
use Andriichuk\BsgSmsChannel\BsgChannel;
use Andriichuk\BsgSmsChannel\Sms;
use Illuminate\Notifications\Notification;

class Invitation extends Notification
{
    public function via(object $notifiable): array
    {
        return [BsgChannel::class];
    }

    public function toBsg(object $notifiable): Sms
    {
        return new Sms(
            text: 'You have been invited!',
            reference: 'invite-'.$notifiable->getKey(),
        );
    }
}
```

Send the notification as usual:

```php
$user->notify(new Invitation());
```

### Anonymous notifications

```php
use Andriichuk\BsgSmsChannel\BsgChannel;
use Illuminate\Support\Facades\Notification;

Notification::route(BsgChannel::class, '+380991112233')
    ->notify(new Invitation());
```

### Message options

`Sms` supports the following named arguments:

- `text` — message body (required).
- `phone` — recipient number; when omitted, the channel resolves it from the notifiable.
- `from` — per-message sender override; defaults to `services.bsg.from`.
- `reference` — external message ID of up to 32 alphanumeric characters.
- `validity` — validity period from 1 to 72 hours.
- `tariff` — tariff number from 0 to 9.
- `twoWay` — marks the message as a 2-way SMS.

```php
return new Sms(
    text: 'Your verification code is 123456',
    validity: 1,
    tariff: 0,
    twoWay: false,
);
```

Phone numbers are normalized to digits before being sent. Optional arguments are omitted from the API request when they are `null`, so BSG's defaults remain in effect.

## Testing

```bash
composer test
```

Static analysis and code style checks are also available:

```bash
composer analyse
composer format
```

## Changelog

See [CHANGELOG](CHANGELOG.md) for release notes.

## Security

Please use the repository's [security policy](../../security/policy) to report vulnerabilities privately.

## Credits

- [Serhii Andriichuk](https://github.com/andriichuk)

## License

The MIT License. See [LICENSE](LICENSE.md) for details.
