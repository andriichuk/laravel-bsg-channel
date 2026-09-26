<?php

declare(strict_types=1);

namespace Andriichuk\BsgSmsChannel;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

/** @see https://bsg.world/developers/rest-api/sending-sms */
class BsgClient
{
    private const string BASE_URI = 'https://api.bsg.world';

    private Client $httpClient;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $originator,
        ?Client $httpClient = null,
    ) {
        $this->httpClient = $httpClient ?? new Client([
            'base_uri' => self::BASE_URI,
        ]);
    }

    /**
     * @param  array{text: string, phone: string, from?: string|null, reference?: string|null, validity?: int|null, tariff?: int|null, two_way?: bool|null}  $parameters
     */
    public function sendSms(array $parameters): SendSmsResponse
    {
        $payload = [
            'destination' => 'phone',
            'msisdn' => self::normalizePhone($parameters['phone']),
            'originator' => $parameters['from'] ?? $this->originator,
            'body' => $parameters['text'],
        ];

        foreach (['reference', 'validity', 'tariff'] as $option) {
            if (array_key_exists($option, $parameters) && $parameters[$option] !== null) {
                $payload[$option] = $parameters[$option];
            }
        }

        if (array_key_exists('two_way', $parameters) && $parameters['two_way'] !== null) {
            $payload['2way'] = $parameters['two_way'];
        }

        $response = $this->httpClient->post('/rest/sms/create', [
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
                'X-API-KEY' => $this->apiKey,
            ],
            RequestOptions::JSON => $payload,
        ]);

        return $this->parseSendSmsResponse((string) $response->getBody());
    }

    public static function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    private function parseSendSmsResponse(string $json): SendSmsResponse
    {
        /** @var mixed $decoded */
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('Could not parse BSG send SMS response JSON.');
        }

        if ((string) ($decoded['error'] ?? '') !== '0') {
            throw BsgApiException::fromResponse($decoded);
        }

        return new SendSmsResponse(
            error: (string) $decoded['error'],
            id: isset($decoded['id']) ? (string) $decoded['id'] : null,
            reference: isset($decoded['reference']) ? (string) $decoded['reference'] : null,
            price: isset($decoded['price']) ? (string) $decoded['price'] : null,
            currency: isset($decoded['currency']) ? (string) $decoded['currency'] : null,
        );
    }
}
