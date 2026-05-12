<?php
namespace App\Modules\Dte\Domain\ValueObjects;
use InvalidArgumentException;
final class Money
{
    public function __construct(private readonly float $amount)
    {
        if($amount <0)
        {
        throw new InvalidArgumentException("El monto no puede ser negativo.");
        }
    }
    public static function zero(): self
    {
        return new self(0.0);
    }
    public function value(): string
    {
        return round($this->amount,2);
    }
    public function add(self $other): self
    {
        return new self(round($this->value() + $other->value(),2));
    }
    public function substract(self $other): self
    {
        $result =round($this->value() - $other->value(), 2);
        if($result < 0)
        {
            $result =0;
        }
        return new self($result);
    }
    public function multiply(float $factor): self
    {
        return new self(round($this->value() * $factor, 2));
    }
}
