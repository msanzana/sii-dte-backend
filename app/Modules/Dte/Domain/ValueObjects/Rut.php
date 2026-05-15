<?php
namespace App\Modules\Dte\Domain\ValueObjects;
use App\Modules\Dte\Domain\Exceptions\InvalidRutException;
final class Rut
{
    private string $full;
    private string $body;
    private string $dv;

    public function __construct(string $rut)
    {
        $normalized = $this->normalize($rut);

        if(!$this->hasValidFormat($normalized)){
            throw InvalidRutException::because($rut);
        }

        [$body, $dv] = explode("-", $normalized);

        if($this->calculateDv($body) !== $dv)
        {
            throw InvalidRutException::because($rut);
        }

        $this->full = $normalized;
        $this->body = $body;
        $this->dv = $dv;
    }

    private function hasValidFormat(string $rut): bool
    {
        return (bool) preg_match('/^\d{7,8}-[\dK]$/', $rut);
    }
    public function full():string
    {
        return $this->full;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function dv(): string
    {
        return $this->dv;
    }

    private function normalize(string $rut):string
    {
        $rut = strtoupper(trim($rut));
        $rut = str_replace('.','',$rut);
        $rut = preg_replace('/\s+/','', $rut);

        if(strpos($rut, '-') === false && strlen($rut) >=2)
        {
            $rut = substr($rut,0,-1).'-'.substr($rut, -1);
        }
        return $rut;
    }

    public function calculateDv(string $body):string
    {
        $sum = 0;
        $multiplier = 2;

        for ($i = strlen($body) -1; $i >= 0; $i--)
        {
            $sum += ((int) $body[$i]) * $multiplier;
            $multiplier = $multiplier === 7 ? 2: $multiplier +1;
        }

        $remainder = 11-($sum % 11);

        if($remainder === 11)
        {
            return '0';
        }
        if($remainder === 10)
        {
            return 'K';
        }

        return (string) $remainder;
    }
}
