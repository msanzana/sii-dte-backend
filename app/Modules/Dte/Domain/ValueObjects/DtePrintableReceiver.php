<?php

namespace App\Modules\Dte\Domain\ValueObjects;

final class DtePrintableReceiver
{
    public function __construct(
        private readonly string $document,
        private readonly string $name,
        private readonly ?string $giro,
        private readonly ?string $address,
        private readonly ?string $city,
        private readonly ?string $comune,
        private readonly ?string $email,
    ) {}

    public function document(): string { return $this->document; }
    public function name(): string { return $this->name; }
    public function giro(): ?string { return $this->giro; }
    public function address(): ?string { return $this->address; }
    public function city(): ?string { return $this->city; }
    public function comune(): ?string { return $this->comune; }
    public function email(): ?string { return $this->email; }
}