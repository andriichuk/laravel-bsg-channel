<?php

use Andriichuk\BsgSmsChannel\BsgApiException;
use Andriichuk\BsgSmsChannel\BsgClient;
use Andriichuk\BsgSmsChannel\SendSmsResponse;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

it('posts a single SMS request with BSG authentication and message options', function () {
    $httpClient = $this->createMock(HttpClient::class);
    $stream = $this->createMock(StreamInterface::class);
    $response = $this->createMock(ResponseInterface::class);

    $stream->method('__toString')->willReturn(json_encode([
        'error' => '0',
        'id' => '22125',
        'reference' => 'invite42',
        'price' => '0.02',
        'currency' => 'EUR',
    ], JSON_THROW_ON_ERROR));
    $response->method('getBody')->willReturn($stream);

    $httpClient->expects($this->once())
        ->method('post')
        ->with(
            '/rest/sms/create',
            $this->callback(function (array $options): bool {
                expect($options[RequestOptions::HEADERS])->toBe([
                    'Accept' => 'application/json',
                    'X-API-KEY' => 'live_test_key',
                ]);
                expect($options[RequestOptions::JSON])->toBe([
                    'destination' => 'phone',
                    'msisdn' => '380991112233',
                    'originator' => 'Sender',
                    'body' => 'Hello & welcome',
                    'reference' => 'invite42',
                    'validity' => 24,
                    'tariff' => 2,
                    '2way' => true,
                ]);

                return true;
            })
        )
        ->willReturn($response);

    $client = new BsgClient('live_test_key', 'Sender', $httpClient);
    $sendResponse = $client->sendSms([
        'text' => 'Hello & welcome',
        'phone' => '+380 99 111-22-33',
        'reference' => 'invite42',
        'validity' => 24,
        'tariff' => 2,
        'two_way' => true,
    ]);

    expect($sendResponse)->toBeInstanceOf(SendSmsResponse::class)
        ->and($sendResponse->toArray())->toBe([
            'error' => '0',
            'id' => '22125',
            'reference' => 'invite42',
            'price' => '0.02',
            'currency' => 'EUR',
        ]);
});

it('uses a per-message originator when provided', function () {
    $httpClient = $this->createMock(HttpClient::class);
    $stream = $this->createMock(StreamInterface::class);
    $response = $this->createMock(ResponseInterface::class);

    $stream->method('__toString')->willReturn('{"error":"0","id":"22125"}');
    $response->method('getBody')->willReturn($stream);

    $httpClient->expects($this->once())
        ->method('post')
        ->with(
            '/rest/sms/create',
            $this->callback(function (array $options): bool {
                expect($options[RequestOptions::JSON]['originator'])->toBe('CustomSender');

                return true;
            })
        )
        ->willReturn($response);

    $client = new BsgClient('live_test_key', 'DefaultSender', $httpClient);
    $client->sendSms([
        'text' => 'Hi',
        'phone' => '380991112233',
        'from' => 'CustomSender',
    ]);
});

it('throws a detailed exception when BSG returns an API error', function () {
    $httpClient = $this->createMock(HttpClient::class);
    $stream = $this->createMock(StreamInterface::class);
    $response = $this->createMock(ResponseInterface::class);

    $stream->method('__toString')->willReturn('{"error":"101","errorDescription":"Invalid API key"}');
    $response->method('getBody')->willReturn($stream);
    $httpClient->expects($this->once())->method('post')->willReturn($response);

    $client = new BsgClient('invalid', 'Sender', $httpClient);
    $client->sendSms([
        'text' => 'Hello',
        'phone' => '+380991112233',
    ]);
})->throws(BsgApiException::class, 'BSG API error 101: Invalid API key');

it('throws when BSG returns malformed JSON', function () {
    $httpClient = $this->createMock(HttpClient::class);
    $stream = $this->createMock(StreamInterface::class);
    $response = $this->createMock(ResponseInterface::class);

    $stream->method('__toString')->willReturn('not-json');
    $response->method('getBody')->willReturn($stream);
    $httpClient->expects($this->once())->method('post')->willReturn($response);

    $client = new BsgClient('live_test_key', 'Sender', $httpClient);
    $client->sendSms([
        'text' => 'Hello',
        'phone' => '+380991112233',
    ]);
})->throws(RuntimeException::class, 'Could not parse BSG send SMS response JSON.');
