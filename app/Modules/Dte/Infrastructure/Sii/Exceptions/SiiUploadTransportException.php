<?php
namespace App\Modules\Dte\Infrastructure\Sii\Exceptions;

use Illuminate\Http\Client\ConnectionException;
use Throwable;

final class SiiUploadTransportException extends ConnectionException
{
    public function __construct(
        string $message,
        private readonly array $diagnostics = [],
        private readonly ?int $httpStatus = null,
        private readonly ?string $recoveredBody = null,
        ?Throwable $previous = null,
    ){
        parent::__construct($message, 0, $previous);
    }

    public function diagnostic(): array
    {
        return $this->diagnostics;
    }
    public function httpStatus(): ?int
    {
        return $this->httpStatus;
    }
    public function recoveredBody(): ?string
    {
        return $this->recoveredBody;
    }
}