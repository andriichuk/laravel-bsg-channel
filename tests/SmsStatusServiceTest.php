<?php

use Andriichuk\Bsg\Contracts\BsgClientInterface;
use Andriichuk\Bsg\Responses\SmsStatusResponse;
use Andriichuk\BsgChannel\Services\SmsStatusService;

it('resolves the SMS status service from the container', function () {
    expect($this->app->make(SmsStatusService::class))->toBeInstanceOf(SmsStatusService::class);
});

it('looks up an SMS status by BSG message ID', function () {
    $response = new SmsStatusResponse(error: '0', id: '211', status: 'delivered');
    $client = $this->createMock(BsgClientInterface::class);
    $client->expects($this->once())->method('getSmsStatus')->with('211')->willReturn($response);

    expect((new SmsStatusService($client))->byId('211'))->toBe($response);
});

it('looks up an SMS status by external reference', function () {
    $response = new SmsStatusResponse(error: '0', reference: 'invite42', status: 'sent');
    $client = $this->createMock(BsgClientInterface::class);
    $client->expects($this->once())->method('getSmsStatusByReference')->with('invite42')->willReturn($response);

    expect((new SmsStatusService($client))->byReference('invite42'))->toBe($response);
});
