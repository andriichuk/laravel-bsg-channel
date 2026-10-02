<?php

declare(strict_types=1);

namespace Andriichuk\BsgChannel\Channels;

use Andriichuk\Bsg\Contracts\BsgClientInterface;
use Andriichuk\Bsg\Responses\SendSmsResponse;
use Andriichuk\BsgChannel\Messages\Sms;
use Illuminate\Container\Attributes\Config;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Stringable;

final readonly class SmsChannel
{
    public function __construct(
        private BsgClientInterface $client,
        private LoggerInterface $logger,
        #[Config('services.bsg.from')]
        private string $originator,
        #[Config('services.bsg.log_response')]
        private bool $logResponse = false,
    ) {}

    public function send(object $notifiable, Notification $notification): SendSmsResponse
    {
        if (! method_exists($notification, 'toBsgSms')) {
            throw new InvalidArgumentException(
                'Notification must implement toBsgSms() method.'
            );
        }

        $message = $notification->toBsgSms($notifiable);

        if (! $message instanceof Sms) {
            throw new InvalidArgumentException(
                'Notification::toBsgSms() must return an instance of '.Sms::class.'.'
            );
        }

        $phone = $message->phone ?? $this->resolvePhone($notifiable);

        if ($phone === '') {
            throw new InvalidArgumentException(
                'Could not determine recipient phone number for BSG SMS notification.'
            );
        }

        $response = $this->client->sendSms($message->toRequest($phone, $this->originator));

        if ($this->logResponse) {
            $this->logger->info('BSG SMS response', [
                'response' => $response->toArray(),
            ]);
        }

        return $response;
    }

    private function resolvePhone(object $notifiable): string
    {
        if (! method_exists($notifiable, 'routeNotificationFor')) {
            return $notifiable instanceof Stringable ? (string) $notifiable : '';
        }

        $phone = $notifiable->routeNotificationFor('bsg-sms');

        if ($phone === null) {
            $phone = $notifiable->routeNotificationFor(self::class);
        }

        return (string) ($phone ?? '');
    }
}
