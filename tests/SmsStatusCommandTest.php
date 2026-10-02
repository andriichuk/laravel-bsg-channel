<?php

use Andriichuk\Bsg\Contracts\BsgClientInterface;
use Andriichuk\Bsg\Responses\SmsStatusResponse;

it('checks SMS status by BSG message ID', function () {
    $client = $this->createMock(BsgClientInterface::class);
    $client->expects($this->once())
        ->method('getSmsStatus')
        ->with('211')
        ->willReturn(new SmsStatusResponse(error: '0', id: '211', status: 'delivered'));

    $this->app->instance(BsgClientInterface::class, $client);

    $this->artisan('bsg:sms-status', ['identifier' => '211'])
        ->expectsOutputToContain('delivered')
        ->assertSuccessful();
});

it('checks SMS status by external reference', function () {
    $client = $this->createMock(BsgClientInterface::class);
    $client->expects($this->once())
        ->method('getSmsStatusByReference')
        ->with('invite42')
        ->willReturn(new SmsStatusResponse(error: '0', reference: 'invite42', status: 'sent'));

    $this->app->instance(BsgClientInterface::class, $client);

    $this->artisan('bsg:sms-status', [
        'identifier' => 'invite42',
        '--reference' => true,
    ])
        ->expectsOutputToContain('sent')
        ->assertSuccessful();
});
