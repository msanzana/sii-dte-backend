<?php
namespace App\Modules\Dte\Domain\Exceptions;
class DocumentNotFoundException extends DomainException
{
    public static function withId(int $documentId): self
    {
        return new self("No existe un documento DTE con el id {$documentId}.");
    }
}
