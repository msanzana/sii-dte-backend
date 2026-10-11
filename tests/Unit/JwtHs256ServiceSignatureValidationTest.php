<?php

namespace Tests\Unit;

use App\Modules\Auth\Domain\Exception\InvalidJwtTokenException;
use App\Modules\Auth\Infrastructure\Security\JwtHs256Service;
use Tests\TestCase;

final class JwtHs256ServiceSignatureValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'platform_auth.jwt.secret',
            'jwt-test-secret-que-no-debe-usarse-en-produccion'
        );

        config()->set(
            'platform_auth.jwt.issuer',
            'sii-dte-backend-test'
        );

        config()->set(
            'platform_auth.jwt.audience',
            'sii-dte-platform-test'
        );
    }

    public function test_token_emitido_correctamente_es_valido(): void
    {
        $service = new JwtHs256Service();

        $token = $service->issue([
            'uid' => 10,
            'stage' => 'access',
            'company_id' => 1,
        ], 600);

        $payload = $service->parseAndValidate($token);

        $this->assertSame(10, $payload['uid']);
        $this->assertSame('access', $payload['stage']);
        $this->assertSame(1, $payload['company_id']);
    }

    public function test_rechaza_token_con_firma_alterada(): void
    {
        $service = new JwtHs256Service();

        $token = $service->issue([
            'uid' => 10,
            'stage' => 'access',
            'company_id' => 1,
        ], 600);

        [$header, $payload, $signature] = explode('.', $token);

        $forgedToken = $header
            . '.'
            . $payload
            . '.'
            . str_repeat('A', strlen($signature));

        $this->expectException(
            InvalidJwtTokenException::class
        );

        $service->parseAndValidate($forgedToken);
    }

    public function test_rechaza_payload_modificado_sin_recalcular_firma(): void
    {
        $service = new JwtHs256Service();

        $token = $service->issue([
            'uid' => 10,
            'stage' => 'access',
            'company_id' => 1,
        ], 600);

        [$header, $payload, $signature] = explode('.', $token);

        $decodedPayload = json_decode(
            $this->base64UrlDecode($payload),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        // Simula un intento de saltar del ámbito de empresa 1 a empresa 999.
        $decodedPayload['company_id'] = 999;

        $modifiedPayload = $this->base64UrlEncode(
            json_encode(
                $decodedPayload,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
            )
        );

        // Se conserva deliberadamente la firma ORIGINAL.
        $forgedToken = $header
            . '.'
            . $modifiedPayload
            . '.'
            . $signature;

        $this->expectException(
            InvalidJwtTokenException::class
        );

        $service->parseAndValidate($forgedToken);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(
            strtr(
                base64_encode($data),
                '+/',
                '-_'
            ),
            '='
        );
    }

    private function base64UrlDecode(string $data): string
    {
        $padding = 4 - (strlen($data) % 4);

        if ($padding !== 4) {
            $data .= str_repeat('=', $padding);
        }

        $decoded = base64_decode(
            strtr($data, '-_', '+/'),
            true
        );

        $this->assertNotFalse($decoded);

        return $decoded;
    }
}
