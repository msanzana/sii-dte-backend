<?php

namespace App\Modules\Auth\Infrastructure\Security;

use App\Modules\Auth\Domain\Exceptions\PasswordResetException;

final class PasswordResetTokenService
{
    public function generateSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function hashSecret(string $secret): string
    {
        return hash('sha256', $secret);
    }

    public function buildPublicToken(int $tokenId, string $secret): string
    {
        return 'prt_' . $tokenId . '.' . $secret;
    }

    /**
     * @return array{id:int,secret:string,hash:string}
     */
    public function parsePublicToken(string $publicToken): array
    {
        if (!preg_match('/^prt_(\d+)\.([A-Fa-f0-9]+)$/', trim($publicToken), $matches)) {
            throw PasswordResetException::because(
                'El token de recuperación no tiene un formato válido.'
            );
        }

        $id = (int) $matches[1];
        $secret = (string) $matches[2];

        return [
            'id' => $id,
            'secret' => $secret,
            'hash' => $this->hashSecret($secret),
        ];
    }
}
