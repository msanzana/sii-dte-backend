<?php

namespace Tests\Unit;
use App\Modules\Dte\Domain\Exceptions\InvalidCafException;
use App\Modules\Dte\Infrastructure\Crypto\SiiCafSignatureKeyResolver;
use App\Modules\Dte\Infrastructure\Crypto\SiiCafSignatureVerifier;
use Tests\TestCase;

final class SiiCafSignatureVerifierTest extends TestCase
{
    public function test_acepta_una_firma_frma_sha1withrsa_valida(): void
    {
$privateKeyPem = <<<'PEM'
-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCBvPzaJEFuCGcC
BHG7iD1jMuNzDnBQUpCo1xMlOispVcGLTVOW3l9dSGm1pjzg5UjsohchnDyVhLEX
4goJvh96MN2j6FUbE9RylrhTxOgDFy74gXkM9tPKS/oIJWvtlYnYllIVAvxHg5rs
3PPj+UGQGSNl5QZJkxjefkfm6szdDMfcVKnFTAWYsk1vNYncRZyT8NkAViJIWTfV
TR3y5agemqS/FjlAiUafWSexTVmWlAOqXHbDEZFbW63yRx9MmkaCwDkqeW0jMgLQ
1gOIaSIxngoDORBdLdGPKM9frOXMSlARC5X1Dno4Lu1uuBPEI9zVzydKSC0oCcJI
VN5bZ6avAgMBAAECggEAAQ8VGfn5x3dn6I4gAPju+Moe/vM7OCydEmDPBKZncCUc
3AXY0qFTGwdxu0C7XzNFtL0ZlWoZZ2ZJIZEivUsSgxATxNsT33oo3b9K/6zDZR0+
maCmW+ujdXCxT+2G/c4qSjnnI8na0DBuzF9lq0zcbTkHTamasytlpJESkYeRFThs
U/O1i9CqYf+8OlVXRQMnod2H41TzLE5vaAxJVl4NSu49SlMzP+i40rEAhYuXSC3c
f9cMfpC6D8c1kCdrCqPZ/mU1NGKORIMT7ARKgq/AplBnKP3bp2/GjuOhwIDbPRyu
RPFN10tggLOUYQDY41RAwRFRCxRL9o28dyhHnvWogQKBgQC2ZV9rOJPrh7SAnKvR
ZiC05YkYntQ6vTMYcFyCGfU112UvkMqzNETMqYURF4GWJAQ8wKFJJKUeE0mUMfqR
99zIDMlJOeuYonx2EDDihk07IEK16wOSplZZCL3y9+jcdxFF14dZIE+mYtvb7Grs
ZKLo5kpD7q/hPCD3pyzE/CUJXwKBgQC2F77Abj/WqMb1bQXoaX9w2g111KnG66mn
V6+6bM9efY50vJeEj1OdZTSHuF8ev/sA69ybLqIZlnkB3Za58TO0ujgK2SyrWv8h
om+JOF4tmwine/a0b8MGTa3neofx8NhmjfOtfatCTjyflmyeU3i7yaIox+WtO6wt
DTix1udUsQKBgC0CHuWqdHXuatBB2PQ3K2L4MThuGRjVj0I8l9dS6Ht8x29RX3OR
Nlj6i+eH0WZnRNRpBGO0MzqUr9dt7dMPQt/qp9D2BfkIP4YywJ1lXrF0aIHTmHIb
sbsOuTC3lDKy/wQpBzErE/yO8In4cPoca0blbPYOdEA0Qj5admW1gr4pAoGAbD2v
MRoYLpTDN+6nvWDA1ad4qttQVKOPhJ72IZ4+ok+GV9QnSTAdpwka8bZiLJg4L3ME
/uX4i7dLlRVQXJWJg9vSJni0OePsluTE4k+0g+2NdmmU2+s3hc61Gk1W9DWTnVqy
SCXUoKR7Gu5DANZjhVU1ZIJ2/8Ph9CHQ2r4BYvECgYEApMW0/PLkj/+7MIZy+SE7
wed30M2vxtw/DPiCkUaQYXjrogsfJUWHU3YMAY5c6Nqq/WLzKN+RP3+GXejf9Y4X
QAIN4YzqvPpGNL9X2HNoh03ey54yb/ikLIRs2oDOGMSmWfkJDtpD+3m0CrOS9BBU
xe85OSMN8q5w+K7Uh67mOBI=
-----END PRIVATE KEY-----
PEM;

$publicKeyPem = <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAgbz82iRBbghnAgRxu4g9
YzLjcw5wUFKQqNcTJTorKVXBi01Tlt5fXUhptaY84OVI7KIXIZw8lYSxF+IKCb4f
ejDdo+hVGxPUcpa4U8ToAxcu+IF5DPbTykv6CCVr7ZWJ2JZSFQL8R4Oa7Nzz4/lB
kBkjZeUGSZMY3n5H5urM3QzH3FSpxUwFmLJNbzWJ3EWck/DZAFYiSFk31U0d8uWo
HpqkvxY5QIlGn1knsU1ZlpQDqlx2wxGRW1ut8kcfTJpGgsA5KnltIzIC0NYDiGki
MZ4KAzkQXS3RjyjPX6zlzEpQEQuV9Q56OC7tbrgTxCPc1c8nSkgtKAnCSFTeW2em
rwIDAQAB
-----END PUBLIC KEY-----
PEM;

        config([
            'dte.sii.caf_signature_keys' => [
                '100' => $publicKeyPem,
            ],
        ]);

        $daXml = <<<'XML'
<DA>
    <RE>76123456-7</RE>
    <TD>39</TD>
    <RNG>
        <D>1</D>
        <H>10</H>
    </RNG>
    <FA>2026-09-17</FA>
    <RSAPK>
        <M>MODULO-PRUEBA</M>
        <E>Aw==</E>
    </RSAPK>
    <IDK>100</IDK>
</DA>
XML;

        $signature = '';

        $signed = openssl_sign(
            $daXml,
            $signature,
            $privateKeyPem,
            OPENSSL_ALGO_SHA1
        );

        if ($signed !== true) {
            $this->fail(
                'No fue posible generar la firma RSA-SHA1 para el test.'
            );
        }

        $frmaValue = base64_encode(
            $signature
        );

        $resolver =
            new SiiCafSignatureKeyResolver();

        $verifier =
            new SiiCafSignatureVerifier(
                $resolver
            );

        $result = $verifier->verify(
            daXml: $daXml,
            frmaValue: $frmaValue,
            algorithm: 'SHA1withRSA',
            siiKeyId: '100',
        );

        $this->assertTrue(
            $result
        );
    }
    public function test_rechaza_la_firma_frma_si_el_da_fue_modificado(): void
    {
    $privateKeyPem = <<<'PEM'
-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCBvPzaJEFuCGcC
BHG7iD1jMuNzDnBQUpCo1xMlOispVcGLTVOW3l9dSGm1pjzg5UjsohchnDyVhLEX
4goJvh96MN2j6FUbE9RylrhTxOgDFy74gXkM9tPKS/oIJWvtlYnYllIVAvxHg5rs
3PPj+UGQGSNl5QZJkxjefkfm6szdDMfcVKnFTAWYsk1vNYncRZyT8NkAViJIWTfV
TR3y5agemqS/FjlAiUafWSexTVmWlAOqXHbDEZFbW63yRx9MmkaCwDkqeW0jMgLQ
1gOIaSIxngoDORBdLdGPKM9frOXMSlARC5X1Dno4Lu1uuBPEI9zVzydKSC0oCcJI
VN5bZ6avAgMBAAECggEAAQ8VGfn5x3dn6I4gAPju+Moe/vM7OCydEmDPBKZncCUc
3AXY0qFTGwdxu0C7XzNFtL0ZlWoZZ2ZJIZEivUsSgxATxNsT33oo3b9K/6zDZR0+
maCmW+ujdXCxT+2G/c4qSjnnI8na0DBuzF9lq0zcbTkHTamasytlpJESkYeRFThs
U/O1i9CqYf+8OlVXRQMnod2H41TzLE5vaAxJVl4NSu49SlMzP+i40rEAhYuXSC3c
f9cMfpC6D8c1kCdrCqPZ/mU1NGKORIMT7ARKgq/AplBnKP3bp2/GjuOhwIDbPRyu
RPFN10tggLOUYQDY41RAwRFRCxRL9o28dyhHnvWogQKBgQC2ZV9rOJPrh7SAnKvR
ZiC05YkYntQ6vTMYcFyCGfU112UvkMqzNETMqYURF4GWJAQ8wKFJJKUeE0mUMfqR
99zIDMlJOeuYonx2EDDihk07IEK16wOSplZZCL3y9+jcdxFF14dZIE+mYtvb7Grs
ZKLo5kpD7q/hPCD3pyzE/CUJXwKBgQC2F77Abj/WqMb1bQXoaX9w2g111KnG66mn
V6+6bM9efY50vJeEj1OdZTSHuF8ev/sA69ybLqIZlnkB3Za58TO0ujgK2SyrWv8h
om+JOF4tmwine/a0b8MGTa3neofx8NhmjfOtfatCTjyflmyeU3i7yaIox+WtO6wt
DTix1udUsQKBgC0CHuWqdHXuatBB2PQ3K2L4MThuGRjVj0I8l9dS6Ht8x29RX3OR
Nlj6i+eH0WZnRNRpBGO0MzqUr9dt7dMPQt/qp9D2BfkIP4YywJ1lXrF0aIHTmHIb
sbsOuTC3lDKy/wQpBzErE/yO8In4cPoca0blbPYOdEA0Qj5admW1gr4pAoGAbD2v
MRoYLpTDN+6nvWDA1ad4qttQVKOPhJ72IZ4+ok+GV9QnSTAdpwka8bZiLJg4L3ME
/uX4i7dLlRVQXJWJg9vSJni0OePsluTE4k+0g+2NdmmU2+s3hc61Gk1W9DWTnVqy
SCXUoKR7Gu5DANZjhVU1ZIJ2/8Ph9CHQ2r4BYvECgYEApMW0/PLkj/+7MIZy+SE7
wed30M2vxtw/DPiCkUaQYXjrogsfJUWHU3YMAY5c6Nqq/WLzKN+RP3+GXejf9Y4X
QAIN4YzqvPpGNL9X2HNoh03ey54yb/ikLIRs2oDOGMSmWfkJDtpD+3m0CrOS9BBU
xe85OSMN8q5w+K7Uh67mOBI=
-----END PRIVATE KEY-----
PEM;

    $publicKeyPem = <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAgbz82iRBbghnAgRxu4g9
YzLjcw5wUFKQqNcTJTorKVXBi01Tlt5fXUhptaY84OVI7KIXIZw8lYSxF+IKCb4f
ejDdo+hVGxPUcpa4U8ToAxcu+IF5DPbTykv6CCVr7ZWJ2JZSFQL8R4Oa7Nzz4/lB
kBkjZeUGSZMY3n5H5urM3QzH3FSpxUwFmLJNbzWJ3EWck/DZAFYiSFk31U0d8uWo
HpqkvxY5QIlGn1knsU1ZlpQDqlx2wxGRW1ut8kcfTJpGgsA5KnltIzIC0NYDiGki
MZ4KAzkQXS3RjyjPX6zlzEpQEQuV9Q56OC7tbrgTxCPc1c8nSkgtKAnCSFTeW2em
rwIDAQAB
-----END PUBLIC KEY-----
PEM;

        config([
            'dte.sii.caf_signature_keys' => [
                '100' => $publicKeyPem,
            ],
        ]);

        /*
        * Este es el DA original.
        */
    $originalDaXml = <<<'XML'
<DA>
    <RE>76123456-7</RE>
    <TD>39</TD>
    <RNG>
        <D>1</D>
        <H>10</H>
    </RNG>
    <FA>2026-09-17</FA>
    <RSAPK>
        <M>MODULO-PRUEBA</M>
        <E>Aw==</E>
    </RSAPK>
    <IDK>100</IDK>
</DA>
XML;

        $signature = '';

        $signed = openssl_sign(
            $originalDaXml,
            $signature,
            $privateKeyPem,
            OPENSSL_ALGO_SHA1
        );

        if ($signed !== true) {
            $this->fail(
                'No fue posible generar la firma RSA-SHA1 para el test.'
            );
        }

        $frmaValue = base64_encode(
            $signature
        );

        /*
        * Modificamos el DA DESPUÉS de generar FRMA.
        *
        * Folio final:
        *     original = 10
        *     alterado = 999
        */
        $modifiedDaXml = str_replace(
            '<H>10</H>',
            '<H>999</H>',
            $originalDaXml
        );

        $resolver =
            new SiiCafSignatureKeyResolver();

        $verifier =
            new SiiCafSignatureVerifier(
                $resolver
            );

        $result = $verifier->verify(
            daXml: $modifiedDaXml,
            frmaValue: $frmaValue,
            algorithm: 'SHA1withRSA',
            siiKeyId: '100',
        );

        $this->assertFalse(
            $result
        );
    }
    public function test_rechaza_una_firma_frma_manipulada(): void
    {
    $privateKeyPem = <<<'PEM'
-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCBvPzaJEFuCGcC
BHG7iD1jMuNzDnBQUpCo1xMlOispVcGLTVOW3l9dSGm1pjzg5UjsohchnDyVhLEX
4goJvh96MN2j6FUbE9RylrhTxOgDFy74gXkM9tPKS/oIJWvtlYnYllIVAvxHg5rs
3PPj+UGQGSNl5QZJkxjefkfm6szdDMfcVKnFTAWYsk1vNYncRZyT8NkAViJIWTfV
TR3y5agemqS/FjlAiUafWSexTVmWlAOqXHbDEZFbW63yRx9MmkaCwDkqeW0jMgLQ
1gOIaSIxngoDORBdLdGPKM9frOXMSlARC5X1Dno4Lu1uuBPEI9zVzydKSC0oCcJI
VN5bZ6avAgMBAAECggEAAQ8VGfn5x3dn6I4gAPju+Moe/vM7OCydEmDPBKZncCUc
3AXY0qFTGwdxu0C7XzNFtL0ZlWoZZ2ZJIZEivUsSgxATxNsT33oo3b9K/6zDZR0+
maCmW+ujdXCxT+2G/c4qSjnnI8na0DBuzF9lq0zcbTkHTamasytlpJESkYeRFThs
U/O1i9CqYf+8OlVXRQMnod2H41TzLE5vaAxJVl4NSu49SlMzP+i40rEAhYuXSC3c
f9cMfpC6D8c1kCdrCqPZ/mU1NGKORIMT7ARKgq/AplBnKP3bp2/GjuOhwIDbPRyu
RPFN10tggLOUYQDY41RAwRFRCxRL9o28dyhHnvWogQKBgQC2ZV9rOJPrh7SAnKvR
ZiC05YkYntQ6vTMYcFyCGfU112UvkMqzNETMqYURF4GWJAQ8wKFJJKUeE0mUMfqR
99zIDMlJOeuYonx2EDDihk07IEK16wOSplZZCL3y9+jcdxFF14dZIE+mYtvb7Grs
ZKLo5kpD7q/hPCD3pyzE/CUJXwKBgQC2F77Abj/WqMb1bQXoaX9w2g111KnG66mn
V6+6bM9efY50vJeEj1OdZTSHuF8ev/sA69ybLqIZlnkB3Za58TO0ujgK2SyrWv8h
om+JOF4tmwine/a0b8MGTa3neofx8NhmjfOtfatCTjyflmyeU3i7yaIox+WtO6wt
DTix1udUsQKBgC0CHuWqdHXuatBB2PQ3K2L4MThuGRjVj0I8l9dS6Ht8x29RX3OR
Nlj6i+eH0WZnRNRpBGO0MzqUr9dt7dMPQt/qp9D2BfkIP4YywJ1lXrF0aIHTmHIb
sbsOuTC3lDKy/wQpBzErE/yO8In4cPoca0blbPYOdEA0Qj5admW1gr4pAoGAbD2v
MRoYLpTDN+6nvWDA1ad4qttQVKOPhJ72IZ4+ok+GV9QnSTAdpwka8bZiLJg4L3ME
/uX4i7dLlRVQXJWJg9vSJni0OePsluTE4k+0g+2NdmmU2+s3hc61Gk1W9DWTnVqy
SCXUoKR7Gu5DANZjhVU1ZIJ2/8Ph9CHQ2r4BYvECgYEApMW0/PLkj/+7MIZy+SE7
wed30M2vxtw/DPiCkUaQYXjrogsfJUWHU3YMAY5c6Nqq/WLzKN+RP3+GXejf9Y4X
QAIN4YzqvPpGNL9X2HNoh03ey54yb/ikLIRs2oDOGMSmWfkJDtpD+3m0CrOS9BBU
xe85OSMN8q5w+K7Uh67mOBI=
-----END PRIVATE KEY-----
PEM;

    $publicKeyPem = <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAgbz82iRBbghnAgRxu4g9
YzLjcw5wUFKQqNcTJTorKVXBi01Tlt5fXUhptaY84OVI7KIXIZw8lYSxF+IKCb4f
ejDdo+hVGxPUcpa4U8ToAxcu+IF5DPbTykv6CCVr7ZWJ2JZSFQL8R4Oa7Nzz4/lB
kBkjZeUGSZMY3n5H5urM3QzH3FSpxUwFmLJNbzWJ3EWck/DZAFYiSFk31U0d8uWo
HpqkvxY5QIlGn1knsU1ZlpQDqlx2wxGRW1ut8kcfTJpGgsA5KnltIzIC0NYDiGki
MZ4KAzkQXS3RjyjPX6zlzEpQEQuV9Q56OC7tbrgTxCPc1c8nSkgtKAnCSFTeW2em
rwIDAQAB
-----END PUBLIC KEY-----
PEM;

    config([
        'dte.sii.caf_signature_keys' => [
            '100' => $publicKeyPem,
        ],
    ]);

    $daXml = <<<'XML'
<DA>
    <RE>76123456-7</RE>
    <TD>39</TD>
    <RNG>
        <D>1</D>
        <H>10</H>
    </RNG>
    <FA>2026-09-17</FA>
    <RSAPK>
        <M>MODULO-PRUEBA</M>
        <E>Aw==</E>
    </RSAPK>
    <IDK>100</IDK>
</DA>
XML;

        $signature = '';

        $signed = openssl_sign(
            $daXml,
            $signature,
            $privateKeyPem,
            OPENSSL_ALGO_SHA1
        );

        if ($signed !== true) {
            $this->fail(
                'No fue posible generar la firma RSA-SHA1 para el test.'
            );
        }

        /*
        * Alteramos un byte de la firma ya generada.
        *
        * El resultado seguirá siendo Base64 válido,
        * pero la firma RSA será incorrecta.
        */
        $signature[0] = chr(
            ord($signature[0]) ^ 0x01
        );

        $modifiedFrmaValue = base64_encode(
            $signature
        );

        $resolver =
            new SiiCafSignatureKeyResolver();

        $verifier =
            new SiiCafSignatureVerifier(
                $resolver
            );

        $result = $verifier->verify(
            daXml: $daXml,
            frmaValue: $modifiedFrmaValue,
            algorithm: 'SHA1withRSA',
            siiKeyId: '100',
        );

        $this->assertFalse(
            $result
        );
    }
    public function test_rechaza_frma_con_base64_invalido(): void
    {
    $publicKeyPem = <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAgbz82iRBbghnAgRxu4g9
YzLjcw5wUFKQqNcTJTorKVXBi01Tlt5fXUhptaY84OVI7KIXIZw8lYSxF+IKCb4f
ejDdo+hVGxPUcpa4U8ToAxcu+IF5DPbTykv6CCVr7ZWJ2JZSFQL8R4Oa7Nzz4/lB
kBkjZeUGSZMY3n5H5urM3QzH3FSpxUwFmLJNbzWJ3EWck/DZAFYiSFk31U0d8uWo
HpqkvxY5QIlGn1knsU1ZlpQDqlx2wxGRW1ut8kcfTJpGgsA5KnltIzIC0NYDiGki
MZ4KAzkQXS3RjyjPX6zlzEpQEQuV9Q56OC7tbrgTxCPc1c8nSkgtKAnCSFTeW2em
rwIDAQAB
-----END PUBLIC KEY-----
PEM;

        config([
            'dte.sii.caf_signature_keys' => [
                '100' => $publicKeyPem,
            ],
        ]);

        $resolver =
            new SiiCafSignatureKeyResolver();

        $verifier =
            new SiiCafSignatureVerifier(
                $resolver
            );

        $this->expectException(
            InvalidCafException::class
        );

        $this->expectExceptionMessage(
            'La firma FRMA del CAF no contiene un Base64 válido.'
        );

        $verifier->verify(
            daXml: '<DA><IDK>100</IDK></DA>',
            frmaValue: '***NO-ES-BASE64***',
            algorithm: 'SHA1withRSA',
            siiKeyId: '100',
        );
    }
    public function test_rechaza_un_algoritmo_frma_no_soportado(): void
    {
        $resolver =
            new SiiCafSignatureKeyResolver();

        $verifier =
            new SiiCafSignatureVerifier(
                $resolver
            );

        $this->expectException(
            InvalidCafException::class
        );

        $this->expectExceptionMessage(
            'Algoritmo FRMA no soportado: SHA256withRSA.'
        );

        $verifier->verify(
            daXml: '<DA><IDK>100</IDK></DA>',
            frmaValue: base64_encode('FIRMA-PRUEBA'),
            algorithm: 'SHA256withRSA',
            siiKeyId: '100',
        );
    }
    public function test_rechaza_un_idk_del_sii_no_configurado(): void
    {
        config([
            'dte.sii.caf_signature_keys' => [],
        ]);

        $resolver =
            new SiiCafSignatureKeyResolver();

        $verifier =
            new SiiCafSignatureVerifier(
                $resolver
            );

        $this->expectException(
            InvalidCafException::class
        );

        $this->expectExceptionMessage(
            'No existe una llave pública del SII configurada para IDK 999.'
        );

        $verifier->verify(
            daXml: '<DA><IDK>999</IDK></DA>',
            frmaValue: base64_encode('FIRMA-PRUEBA'),
            algorithm: 'SHA1withRSA',
            siiKeyId: '999',
        );
    }
    public function test_verifica_frma_eliminando_whitespace_entre_etiquetas_del_da(): void
    {
        /*
        * Usa aquí exactamente los mismos:
        *
        * $privateKeyPem
        * $publicKeyPem
        *
        * que ya utilizas en los tests anteriores.
        */
$privateKeyPem = <<<'PEM'
-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCBvPzaJEFuCGcC
BHG7iD1jMuNzDnBQUpCo1xMlOispVcGLTVOW3l9dSGm1pjzg5UjsohchnDyVhLEX
4goJvh96MN2j6FUbE9RylrhTxOgDFy74gXkM9tPKS/oIJWvtlYnYllIVAvxHg5rs
3PPj+UGQGSNl5QZJkxjefkfm6szdDMfcVKnFTAWYsk1vNYncRZyT8NkAViJIWTfV
TR3y5agemqS/FjlAiUafWSexTVmWlAOqXHbDEZFbW63yRx9MmkaCwDkqeW0jMgLQ
1gOIaSIxngoDORBdLdGPKM9frOXMSlARC5X1Dno4Lu1uuBPEI9zVzydKSC0oCcJI
VN5bZ6avAgMBAAECggEAAQ8VGfn5x3dn6I4gAPju+Moe/vM7OCydEmDPBKZncCUc
3AXY0qFTGwdxu0C7XzNFtL0ZlWoZZ2ZJIZEivUsSgxATxNsT33oo3b9K/6zDZR0+
maCmW+ujdXCxT+2G/c4qSjnnI8na0DBuzF9lq0zcbTkHTamasytlpJESkYeRFThs
U/O1i9CqYf+8OlVXRQMnod2H41TzLE5vaAxJVl4NSu49SlMzP+i40rEAhYuXSC3c
f9cMfpC6D8c1kCdrCqPZ/mU1NGKORIMT7ARKgq/AplBnKP3bp2/GjuOhwIDbPRyu
RPFN10tggLOUYQDY41RAwRFRCxRL9o28dyhHnvWogQKBgQC2ZV9rOJPrh7SAnKvR
ZiC05YkYntQ6vTMYcFyCGfU112UvkMqzNETMqYURF4GWJAQ8wKFJJKUeE0mUMfqR
99zIDMlJOeuYonx2EDDihk07IEK16wOSplZZCL3y9+jcdxFF14dZIE+mYtvb7Grs
ZKLo5kpD7q/hPCD3pyzE/CUJXwKBgQC2F77Abj/WqMb1bQXoaX9w2g111KnG66mn
V6+6bM9efY50vJeEj1OdZTSHuF8ev/sA69ybLqIZlnkB3Za58TO0ujgK2SyrWv8h
om+JOF4tmwine/a0b8MGTa3neofx8NhmjfOtfatCTjyflmyeU3i7yaIox+WtO6wt
DTix1udUsQKBgC0CHuWqdHXuatBB2PQ3K2L4MThuGRjVj0I8l9dS6Ht8x29RX3OR
Nlj6i+eH0WZnRNRpBGO0MzqUr9dt7dMPQt/qp9D2BfkIP4YywJ1lXrF0aIHTmHIb
sbsOuTC3lDKy/wQpBzErE/yO8In4cPoca0blbPYOdEA0Qj5admW1gr4pAoGAbD2v
MRoYLpTDN+6nvWDA1ad4qttQVKOPhJ72IZ4+ok+GV9QnSTAdpwka8bZiLJg4L3ME
/uX4i7dLlRVQXJWJg9vSJni0OePsluTE4k+0g+2NdmmU2+s3hc61Gk1W9DWTnVqy
SCXUoKR7Gu5DANZjhVU1ZIJ2/8Ph9CHQ2r4BYvECgYEApMW0/PLkj/+7MIZy+SE7
wed30M2vxtw/DPiCkUaQYXjrogsfJUWHU3YMAY5c6Nqq/WLzKN+RP3+GXejf9Y4X
QAIN4YzqvPpGNL9X2HNoh03ey54yb/ikLIRs2oDOGMSmWfkJDtpD+3m0CrOS9BBU
xe85OSMN8q5w+K7Uh67mOBI=
-----END PRIVATE KEY-----
PEM;

$publicKeyPem = <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAgbz82iRBbghnAgRxu4g9
YzLjcw5wUFKQqNcTJTorKVXBi01Tlt5fXUhptaY84OVI7KIXIZw8lYSxF+IKCb4f
ejDdo+hVGxPUcpa4U8ToAxcu+IF5DPbTykv6CCVr7ZWJ2JZSFQL8R4Oa7Nzz4/lB
kBkjZeUGSZMY3n5H5urM3QzH3FSpxUwFmLJNbzWJ3EWck/DZAFYiSFk31U0d8uWo
HpqkvxY5QIlGn1knsU1ZlpQDqlx2wxGRW1ut8kcfTJpGgsA5KnltIzIC0NYDiGki
MZ4KAzkQXS3RjyjPX6zlzEpQEQuV9Q56OC7tbrgTxCPc1c8nSkgtKAnCSFTeW2em
rwIDAQAB
-----END PUBLIC KEY-----
PEM;
        config([
            'dte.sii.caf_signature_keys' => [
                '100' => $publicKeyPem,
            ],
        ]);

        /*
        * Esta es la representación sobre la cual simulamos
        * que el SII generó FRMA.
        *
        * No contiene whitespace entre etiquetas.
        */
        $signedDaXml =
            '<DA>'
            . '<RE>76123456-7</RE>'
            . '<TD>39</TD>'
            . '<RNG><D>1</D><H>10</H></RNG>'
            . '<FA>2026-09-17</FA>'
            . '<RSAPK>'
            . '<M>MODULO-PRUEBA</M>'
            . '<E>Aw==</E>'
            . '</RSAPK>'
            . '<IDK>100</IDK>'
            . '</DA>';

        $signature = '';

        $signed = openssl_sign(
            $signedDaXml,
            $signature,
            $privateKeyPem,
            OPENSSL_ALGO_SHA1
        );

        if ($signed !== true) {
            $this->fail(
                'No fue posible generar la firma RSA-SHA1 para el test.'
            );
        }

        $frmaValue = base64_encode(
            $signature
        );

        /*
        * Esto representa cómo puede venir DA en el XML
        * real descargado desde el SII.
        *
        * Los DATOS son idénticos.
        * Sólo cambia el formato entre etiquetas.
        */
    $receivedDaXml = <<<'XML'
<DA>
    <RE>76123456-7</RE>
    <TD>39</TD>
    <RNG>
        <D>1</D>
        <H>10</H>
    </RNG>
    <FA>2026-09-17</FA>
    <RSAPK>
        <M>MODULO-PRUEBA</M>
        <E>Aw==</E>
    </RSAPK>
    <IDK>100</IDK>
</DA>
XML;

        $verifier = new SiiCafSignatureVerifier(
            new SiiCafSignatureKeyResolver()
        );

        $result = $verifier->verify(
            daXml: $receivedDaXml,
            frmaValue: $frmaValue,
            algorithm: 'SHA1withRSA',
            siiKeyId: '100',
        );

        $this->assertTrue(
            $result
        );
    }
}