<?php

use Andriichuk\Bsg\Contracts\BsgClientInterface;
use Andriichuk\Bsg\Requests\SendSmsRequest;
use Andriichuk\Bsg\Responses\SendSmsResponse;
use Andriichuk\BsgChannel\Channels\SmsChannel;
use Andriichuk\BsgChannel\Messages\Sms;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

it('sends an SMS and returns the SDK response', function () {
    $client = $this->createMock(BsgClientInterface::class);
    $response = new SendSmsResponse('0', '22125', 'invite42', '0.02', 'EUR');

    $client->expects($this->once())
        ->method('sendSms')
        ->with($this->callback(function (SendSmsRequest $request): bool {
            expect($request->toArray())->toBe([
                'destination' => 'phone',
                'msisdn' => '123456789',
                'originator' => 'DefaultSender',
                'body' => 'Test message',
                'reference' => 'invite42',
            ]);

            return true;
        }))
        ->willReturn($response);

    $channel = new SmsChannel($client, new NullLogger, 'DefaultSender');
    $notifiable = new class extends Model
    {
        use Notifiable;

        public function routeNotificationForBsgSms(): string
        {
            return '+123456789';
        }
    };
    $notification = new class extends Notification
    {
        public function toBsgSms(object $notifiable): Sms
        {
            return new Sms(text: 'Test message', reference: 'invite42');
        }
    };

    expect($channel->send($notifiable, $notification))->toBe($response);
});

it('resolves the phone using the full SMS channel class name', function () {
    $client = $this->createMock(BsgClientInterface::class);

    $client->expects($this->once())
        ->method('sendSms')
        ->with($this->callback(function (SendSmsRequest $request): bool {
            expect($request->msisdn)->toBe('+987654321');

            return true;
        }))
        ->willReturn(new SendSmsResponse('0', '1'));

    $channel = new SmsChannel($client, new NullLogger, 'Sender');
    $notifiable = new class extends Model
    {
        use Notifiable;

        public function routeNotificationFor($driver): ?string
        {
            return match ($driver) {
                'bsg-sms' => null,
                SmsChannel::class => '+987654321',
                default => null,
            };
        }
    };
    $notification = new class extends Notification
    {
        public function toBsgSms(object $notifiable): Sms
        {
            return new Sms(text: 'Class based message');
        }
    };

    $channel->send($notifiable, $notification);
});

it('sends an SMS for an anonymous notifiable route', function () {
    $client = $this->createMock(BsgClientInterface::class);

    $client->expects($this->once())
        ->method('sendSms')
        ->with($this->callback(function (SendSmsRequest $request): bool {
            expect($request->msisdn)->toBe('+380991112233');

            return true;
        }))
        ->willReturn(new SendSmsResponse('0', '1'));

    $this->app->instance(BsgClientInterface::class, $client);
    config()->set('services.bsg.from', 'Sender');
    config()->set('services.bsg.log_response', false);

    NotificationFacade::route('bsg-sms', '+380991112233')
        ->notify(new class extends Notification
        {
            public function via(object $notifiable): array
            {
                return ['bsg-sms'];
            }

            public function toBsgSms(object $notifiable): Sms
            {
                return new Sms(text: 'Anonymous message');
            }
        });
});

it('uses a per-message sender override', function () {
    $client = $this->createMock(BsgClientInterface::class);

    $client->expects($this->once())
        ->method('sendSms')
        ->with($this->callback(function (SendSmsRequest $request): bool {
            expect($request->originator)->toBe('CustomSender');

            return true;
        }))
        ->willReturn(new SendSmsResponse('0', '1'));

    $channel = new SmsChannel($client, new NullLogger, 'DefaultSender');
    $channel->send(new class
    {
        public function __toString(): string
        {
            return '+123456789';
        }
    }, new class extends Notification
    {
        public function toBsgSms(object $notifiable): Sms
        {
            return new Sms(text: 'Test', from: 'CustomSender');
        }
    });
});

it('throws when the notification does not implement toBsgSms', function () {
    $channel = new SmsChannel($this->createMock(BsgClientInterface::class), new NullLogger, 'Sender');

    $channel->send(new class {}, new class extends Notification {});
})->throws(InvalidArgumentException::class, 'Notification must implement toBsgSms() method.');

it('throws when toBsgSms does not return an SMS message', function () {
    $channel = new SmsChannel($this->createMock(BsgClientInterface::class), new NullLogger, 'Sender');
    $notification = new class extends Notification
    {
        public function toBsgSms(object $notifiable): string
        {
            return 'not an SMS message';
        }
    };

    $channel->send(new class {}, $notification);
})->throws(InvalidArgumentException::class, 'Notification::toBsgSms() must return an instance of '.Sms::class.'.');

it('throws when the phone cannot be resolved', function () {
    $channel = new SmsChannel($this->createMock(BsgClientInterface::class), new NullLogger, 'Sender');
    $notification = new class extends Notification
    {
        public function toBsgSms(object $notifiable): Sms
        {
            return new Sms(text: 'Test');
        }
    };

    $channel->send(new class {}, $notification);
})->throws(InvalidArgumentException::class, 'Could not determine recipient phone number for BSG SMS notification.');

it('logs the SDK response when configured', function () {
    $client = $this->createMock(BsgClientInterface::class);
    $response = new SendSmsResponse('0', '633217', 'invite42', '0.02', 'EUR');
    $client->method('sendSms')->willReturn($response);

    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())
        ->method('info')
        ->with('BSG SMS response', ['response' => $response->toArray()]);

    $channel = new SmsChannel($client, $logger, 'Sender', true);
    $channel->send(new class
    {
        public function __toString(): string
        {
            return '+123456789';
        }
    }, new class extends Notification
    {
        public function toBsgSms(object $notifiable): Sms
        {
            return new Sms(text: 'Test');
        }
    });
});
