<?php

use Andriichuk\BsgSmsChannel\BsgChannel;
use Andriichuk\BsgSmsChannel\BsgClient;
use Andriichuk\BsgSmsChannel\SendSmsResponse;
use Andriichuk\BsgSmsChannel\Sms;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

it('sends sms via BsgChannel for notifiable model', function () {
    $client = $this->createMock(BsgClient::class);

    $client->expects($this->once())
        ->method('sendSms')
        ->with($this->callback(function (array $params): bool {
            expect($params)->toMatchArray([
                'text' => 'Test message',
                'phone' => '+123456789',
            ]);

            return true;
        }))
        ->willReturn(new SendSmsResponse('0', '1'));

    $channel = new BsgChannel($client, new NullLogger, false);

    $notifiable = new class extends Model
    {
        use Notifiable;

        public string $phone = '+123456789';

        public function routeNotificationForBsg(): string
        {
            return $this->phone;
        }
    };

    $notification = new class extends Notification
    {
        public function via($notifiable): array
        {
            return ['bsg'];
        }

        public function toBsg($notifiable): Sms
        {
            return new Sms(text: 'Test message');
        }
    };

    $channel->send($notifiable, $notification);
});

it('resolves phone using full channel class name', function () {
    $client = $this->createMock(BsgClient::class);

    $client->expects($this->once())
        ->method('sendSms')
        ->with($this->callback(function (array $params): bool {
            expect($params)->toMatchArray([
                'text' => 'Class based message',
                'phone' => '+987654321',
            ]);

            return true;
        }))
        ->willReturn(new SendSmsResponse('0', '1'));

    $channel = new BsgChannel($client, new NullLogger, false);

    $notifiable = new class extends Model
    {
        use Notifiable;

        public function routeNotificationFor($driver): ?string
        {
            return match ($driver) {
                'bsg' => null,
                BsgChannel::class => '+987654321',
                default => null,
            };
        }
    };

    $notification = new class extends Notification
    {
        public function via($notifiable): array
        {
            return [BsgChannel::class];
        }

        public function toBsg($notifiable): Sms
        {
            return new Sms(text: 'Class based message');
        }
    };

    $channel->send($notifiable, $notification);
});

it('sends sms for anonymous notifiable route', function () {
    $client = $this->createMock(BsgClient::class);

    $client->expects($this->once())
        ->method('sendSms')
        ->with($this->callback(function (array $params): bool {
            expect($params)->toMatchArray([
                'text' => 'Anonymous message',
                'phone' => '+380991112233',
            ]);

            return true;
        }))
        ->willReturn(new SendSmsResponse('0', '1'));

    $this->app->instance(BsgClient::class, $client);

    NotificationFacade::route('bsg', '+380991112233')
        ->notify(new class extends Notification
        {
            public function via($notifiable): array
            {
                return ['bsg'];
            }

            public function toBsg($notifiable): Sms
            {
                return new Sms(text: 'Anonymous message');
            }
        });
});

it('throws when notification does not implement toBsg', function () {
    $channel = new BsgChannel($this->createMock(BsgClient::class), new NullLogger, false);

    $notifiable = new class extends Model
    {
        use Notifiable;
    };

    $notification = new class extends Notification
    {
        public function via($notifiable): array
        {
            return ['bsg'];
        }
    };

    $channel->send($notifiable, $notification);
})->throws(InvalidArgumentException::class, 'Notification must implement toBsg() method.');

it('throws when toBsg does not return Sms instance', function () {
    $channel = new BsgChannel($this->createMock(BsgClient::class), new NullLogger, false);

    $notifiable = new class extends Model
    {
        use Notifiable;

        public function routeNotificationForBsg(): string
        {
            return '+123456789';
        }
    };

    $notification = new class extends Notification
    {
        public function via($notifiable): array
        {
            return ['bsg'];
        }

        public function toBsg($notifiable): string
        {
            return 'not an Sms instance';
        }
    };

    $channel->send($notifiable, $notification);
})->throws(InvalidArgumentException::class, 'Notification::toBsg() must return an instance of '.Sms::class.'.');

it('throws when phone cannot be resolved from notifiable', function () {
    $channel = new BsgChannel($this->createMock(BsgClient::class), new NullLogger, false);

    $notifiable = new class extends Model
    {
        use Notifiable;

        public function routeNotificationForBsg(): string
        {
            return '';
        }
    };

    $notification = new class extends Notification
    {
        public function via($notifiable): array
        {
            return ['bsg'];
        }

        public function toBsg($notifiable): Sms
        {
            return new Sms(text: 'Test');
        }
    };

    $channel->send($notifiable, $notification);
})->throws(InvalidArgumentException::class, 'Could not determine recipient phone number for BSG SMS notification.');

it('logs response body when configured', function () {
    $client = $this->createMock(BsgClient::class);
    $response = new SendSmsResponse(
        error: '0',
        id: '633217',
        reference: 'invite-42',
        price: '0.02',
        currency: 'EUR',
    );

    $client->expects($this->once())
        ->method('sendSms')
        ->willReturn($response);

    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())
        ->method('info')
        ->with('BSG SMS response', [
            'response' => [
                'error' => '0',
                'id' => '633217',
                'reference' => 'invite-42',
                'price' => '0.02',
                'currency' => 'EUR',
            ],
        ]);

    $channel = new BsgChannel($client, $logger, true);

    $notifiable = new class extends Model
    {
        use Notifiable;

        public function routeNotificationForBsg(): string
        {
            return '+123456789';
        }
    };

    $notification = new class extends Notification
    {
        public function via($notifiable): array
        {
            return ['bsg'];
        }

        public function toBsg($notifiable): Sms
        {
            return new Sms(text: 'Test message');
        }
    };

    $channel->send($notifiable, $notification);
});
