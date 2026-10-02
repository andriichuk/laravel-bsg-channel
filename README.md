# BSG World Notification Channels for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/andriichuk/laravel-bsg-channel.svg?style=flat-square)](https://packagist.org/packages/andriichuk/laravel-bsg-channel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/andriichuk/laravel-bsg-channel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/andriichuk/laravel-bsg-channel/actions/workflows/run-tests.yml)

Laravel notification channels for the [BSG World API](https://bsg.world/developers/rest-api). The package currently provides an SMS channel and is structured to add other BSG channels, such as Viber, without coupling them to the SMS implementation.

HTTP communication and typed API responses are provided by the framework-agnostic [BSG PHP SDK](https://github.com/andriichuk/bsg-php-sdk).

## Installation

**Requirements:** PHP 8.3+ and Laravel 11.x, 12.x, or 13.5+.

```bash
composer require andriichuk/laravel-bsg-channel
```

The service provider is auto-discovered by Laravel.

Upgrading from `andriichuk/laravel-bsg-sms-channel`? See [UPGRADING.md](UPGRADING.md) for the namespace and notification-method changes.

## Configuration

Add your BSG API key and registered SMS sender name to `config/services.php`:

```php
return [
    // ...

    'bsg' => [
        'api_key' => env('BSG_API_KEY'),
        'from' => env('BSG_SMS_FROM'),
        'log_response' => env('BSG_LOG_RESPONSE', false),
    ],
];
```

```dotenv
BSG_API_KEY="live_your_api_key"
BSG_SMS_FROM="YourSender"
BSG_LOG_RESPONSE=false
```

## SMS notifications

### Notifiable model

Return the recipient's full international phone number from `routeNotificationForBsgSms()`:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public function routeNotificationForBsgSms(): string
    {
        return $this->phone; // e.g. +380991112233
    }
}
```

### Notification class

```php
use Andriichuk\BsgChannel\Channels\SmsChannel;
use Andriichuk\BsgChannel\Messages\Sms;
use Illuminate\Notifications\Notification;

class Invitation extends Notification
{
    public function via(object $notifiable): array
    {
        return [SmsChannel::class];
    }

    public function toBsgSms(object $notifiable): Sms
    {
        return new Sms(
            text: 'You have been invited!',
            reference: 'invite'.$notifiable->getKey(),
        );
    }
}
```

Send the notification as usual:

```php
$user->notify(new Invitation());
```

For an anonymous recipient, use either the channel class or its `bsg-sms` driver name:

```php
use Andriichuk\BsgChannel\Channels\SmsChannel;
use Illuminate\Support\Facades\Notification;

Notification::route(SmsChannel::class, '+380991112233')
    ->notify(new Invitation());
```

### SMS message options

`Sms` accepts these named arguments:

- `text` — message body (required).
- `phone` — per-message recipient override.
- `from` — per-message sender override.
- `reference` — external message ID of up to 32 characters.
- `validity` — validity period from 1 to 72 hours.
- `tariff` — tariff number from 0 to 9.
- `twoWay` — marks the message as a 2-way SMS.

## Tracking SMS status

Inject `SmsStatusService` into an application service, controller, job, or command. No facade is required:

```php
use Andriichuk\Bsg\Responses\SmsStatusResponse;
use Andriichuk\BsgChannel\Services\SmsStatusService;

final readonly class TrackInvitationSms
{
    public function __construct(private SmsStatusService $smsStatuses) {}

    public function byBsgId(string $id): SmsStatusResponse
    {
        return $this->smsStatuses->byId($id);
    }

    public function byReference(string $reference): SmsStatusResponse
    {
        return $this->smsStatuses->byReference($reference);
    }
}
```

The result exposes the BSG delivery data directly:

```php
$status = $smsStatuses->byReference('invite42');

$status->status;      // e.g. "delivered"
$status->timeDr;      // delivery-report time in UTC
$status->msisdn;
$status->price;
$status->currency;
$status->toArray();
```

BSG API failures throw `Andriichuk\Bsg\Exceptions\BsgApiException`.

## Architecture

- `Channels\SmsChannel` adapts Laravel notifications to BSG SMS requests.
- `Messages\Sms` contains Laravel-facing message options.
- `Services\SmsStatusService` provides injectable status lookups.
- `andriichuk/bsg-php-sdk` owns HTTP requests, authentication, API errors, and response objects.

A future Viber integration can add its own channel and message class while sharing the SDK client and package configuration.

## Development

```bash
composer test
composer analyse
composer format
```

## License

The MIT License. See [LICENSE](LICENSE.md) for details.
