<?php
namespace App\Modules\Dte\Domain\Services;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Exceptions\InvalidDteException;
final class DteDocumentValidationDomainService
{
    public function validateForCreation (DteDocument $document): void
    {
        if(count($document->items()) === 0)
        {
            throw InvalidDteException::because(
                'El documento debe tener al menos una línea de detalle.'
            );
        }
        if(count($document->items()) > config('dte.limits.max_detail_lines'))
        {
            throw InvalidDteException::because(
                'El documento no puede tener más de ' . config('dte.limits.max_detail_lines') . ' líneas de detalle.'
            );
        }
        if(trim($document->receiver()->document()) === '')
        {
            throw InvalidDteException::because(
                'el receptor debe tener documento.'
            );
        }
        if(trim($document->receiver()->name()) === '')
        {
            throw InvalidDteException::because(
                'el receptor debe tener nombre.'
            );
        }
        if($document->receiver()->address() !== null && trim($document->receiver()->address()) === '')
        {
            throw InvalidDteException::because(
                'si el receptor tiene dirección, esta no puede estar vacía.'
            );
        }
        if($document->receiver()->CityId() !== null && trim($document->receiver()->CityId()) === '')
        {
            throw InvalidDteException::because(
                'El city_id del receptor es invalido.'
            );
        }
    }
}
