<?php

declare(strict_types=1);

namespace Andriichuk\BsgSmsChannel;

use Illuminate\Contracts\Support\Arrayable;

final readonly class SendSmsResponse implements Arrayable
{
    public function __construct(
        public string $error,
        public ?string $id = null,
        public ?string $reference = null,
        public ?string $price = null,
        public ?string $currency = null,
    ) {}

    /** @return array{error: string, id: string|null, reference: string|null, price: string|null, currency: string|null} */
    public function toArray(): array
    {
        return [
            'error' => $this->error,
            'id' => $this->id,
            'reference' => $this->reference,
            'price' => $this->price,
            'currency' => $this->currency,
        ];
    }
}
