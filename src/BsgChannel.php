<?php

declare(strict_types=1);

namespace Andriichuk\BsgSmsChannel;

use Illuminate\Container\Attributes\Config;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

final readonly class BsgChannel
{
    public function __construct(
        private BsgClient $bsgClient,
        private LoggerInterface $logger,
        #[Config('services.bsg.log_response')]
        private bool $logResponse = false,
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toBsg')) {
            throw new InvalidArgumentException(
                'Notification must implement toBsg() method.'
            );
        }

        $message = $notification->toBsg($notifiable);

        if (! $message instanceof Sms) {
            throw new InvalidArgumentException(
                'Notification::toBsg() must return an instance of '.Sms::class.'.'
            );
        }

        $data = $message->toArray();

        if (($data['phone'] ?? '') === '') {
            $data['phone'] = $this->resolvePhone($notifiable);
        }

        if ($data['phone'] === '') {
            throw new InvalidArgumentException(
                'Could not determine recipient phone number for BSG SMS notification.'
            );
        }

        $response = $this->bsgClient->sendSms($data);

        if ($this->logResponse) {
            $this->logger->info('BSG SMS response', [
                'response' => $response->toArray(),
            ]);
        }
    }

    private function resolvePhone(object $notifiable): string
    {
        if (! method_exists($notifiable, 'routeNotificationFor')) {
            return (string) $notifiable;
        }

        $phone = $notifiable->routeNotificationFor('bsg');

        if ($phone === null) {
            $phone = $notifiable->routeNotificationFor(self::class);
        }

        return (string) ($phone ?? '');
    }
}
