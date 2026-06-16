<?php
namespace App\Modules\Auth\Infrastructure\Security;

use App\Modules\Auth\Domain\Exception\InvalidJwtTokenException;

final class JwtHs256Service
{
    public function issue(array $customClaims, int $ttlSeconds): string
    {
        $secret = $this->secret();
        $now = time();

        $payload = array_merge([
            'iss' => (string) config('platform_auth.jwt.issuer'),
            'aud' => (string) config('platform_auth.jwt.audience'),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttlSeconds,
            'jti' => bin2hex(random_bytes(16)),
        ], $customClaims);

        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256',
        ];

        $encodedHeader = $this->base64UrlEncode(
            json_encode($header, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );

        $encodedPayload = $this->base64UrlEncode(
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );

        $signature = hash_hmac('sha256', "{$encodedHeader}.{$encodedPayload}", $secret,true);

        $encodedSignature = $this->base64UrlEncode($signature);

        return "{$encodedHeader}.{$encodedPayload}.{$encodedSignature}";
    }

    public function parseAndValidate(string $jwt): array
    {
        $parts = explode('.',trim($jwt));

        if(count($parts) !== 3)
        {
            throw InvalidJwtTokenException::because('La firma del JWT no es válida.');
        }

        [$encodedHeader,$encodedPayload,$encodedSignature] = $parts;

        $espectedSignature = $this->base64UrlEncode(
            hash_hmac(
                'sha256',
                "{$encodedHeader}.{$encodedPayload}",
                $this->secret(),
                true
            )
        );

        $headerJson = $this->base64UrlDecode($encodedHeader);
        $payloadJson = $this->base64UrlDecode($encodedPayload);

        $header = json_decode($headerJson,true,212,JSON_THROW_ON_ERROR);
        $payload = json_decode($payloadJson,true,512,JSON_THROW_ON_ERROR);

        if(($header['alg'] ?? null) !== 'HS256')
        {
            throw InvalidJwtTokenException::because('El algoritmo del JWT no es válido');
        }

        $now = time();

        if(!isset($payload['nbf']) || $now < (int) $payload['nbf'])
        {
            throw InvalidJwtTokenException::because('El JWT aun no esta habilitado.');
        }

        if(!isset($payload['exp']) || $now >= (int) $payload['exp'])
        {
            throw InvalidJwtTokenException::because('El JWT ha expirado.');
        }

        if(($payload['iss']  ?? null ) !== (string) config('platform_auth.jwt.issuer'))
        {
            throw InvalidJwtTokenException::because('El emisor del JWT no coincide.');
        }

        if(($payload['aud'] ?? null) !== (string) config('platform_auth.jwt.audience'))
        {
            throw InvalidJwtTokenException::because('La audiencia del JWT no coincide.');
        }

        return $payload;
    }

    private function secret(): string
    {
        $secret = trim((string) config('platform_auth.jwt.secret'));
        if($secret === '')
        {
            throw InvalidJwtTokenException::because(
                'No existe PLATFORM_AUTH_JWT_SECRET configurado.'
            );
        }

        return $secret;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data),'+/','-_'),'=');
    }

    private function base64UrlDecode(string $data): string
    {
        $padding = 4 - (strlen($data) % 4);

        if($padding !== 4)
        {
            $data .= str_repeat('=', $padding);
        }

        $decoded = base64_decode(strtr($data,'-_','+/'),true);

        if($decoded === false)
        {
            throw InvalidJwtTokenException::because('No fue posible decodificar el JWT.');
        }
        return $decoded;
    }
}
