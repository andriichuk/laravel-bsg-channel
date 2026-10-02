<?php

declare(strict_types=1);

namespace Andriichuk\BsgChannel\Messages;

use Andriichuk\Bsg\Requests\SendSmsRequest;

final readonly class Sms
{
    public function __construct(
        public string $text,
        public ?string $phone = null,
        public ?string $from = null,
        public ?string $reference = null,
        public ?int $validity = null,
        public ?int $tariff = null,
        public ?bool $twoWay = null,
    ) {}

    public function toRequest(string $phone, string $defaultOriginator): SendSmsRequest
    {
        return new SendSmsRequest(
            msisdn: $phone,
            originator: $this->from ?? $defaultOriginator,
            body: $this->text,
            reference: $this->reference,
            validity: $this->validity,
            tariff: $this->tariff,
            twoWay: $this->twoWay,
        );
    }
}
