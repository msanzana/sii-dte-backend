<?php
namespace App\Modules\Dte\Domain\ValueObjects;
final class ReceiverData
{
    public function __construct(
        private readonly string $document,
        private readonly string $name,
        private readonly ?string $giro = null,
        private readonly ?string $address = null,
        private readonly ?int $cityId = null,
        private readonly ?string $email = null,
    )
    {}
    public function document():string
    {
        return $this->document;
    }
    public function name(): string
    {
        return $this->name;
    }
    public function giro(): ?string
    {
        return $this->giro;
    }
    public function address(): ?string
    {
        return $this->address;
    }
    public function CityId(): ?int
    {
        return $this->cityId;
    }
    public function email(): ?string
    {
        return $this->email;
    }

}
