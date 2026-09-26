<?php

declare(strict_types=1);

namespace Andriichuk\BsgSmsChannel;

use Illuminate\Contracts\Support\Arrayable;

final readonly class Sms implements Arrayable
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

    /**
     * @return array{text: string, phone: string|null, from?: string, reference?: string, validity?: int, tariff?: int, two_way?: bool}
     */
    public function toArray(): array
    {
        $data = [
            'text' => $this->text,
            'phone' => $this->phone,
        ];

        if ($this->from !== null) {
            $data['from'] = $this->from;
        }

        if ($this->reference !== null) {
            $data['reference'] = $this->reference;
        }

        if ($this->validity !== null) {
            $data['validity'] = $this->validity;
        }

        if ($this->tariff !== null) {
            $data['tariff'] = $this->tariff;
        }

        if ($this->twoWay !== null) {
            $data['two_way'] = $this->twoWay;
        }

        return $data;
    }
}
