<?php

namespace Tests\Unit;

use App\Modules\Dte\Infrastructure\Sii\SiiSeedXmlSignerService;
use App\Modules\Dte\Infrastructure\Sii\SiiSoapAuthenticationService;
use DOMDocument;
use DOMXPath;
use ReflectionClass;
use Tests\TestCase;

final class SiiSeedXmlSignerServiceTest extends TestCase
{
    public function test_el_firmador_compartido_genera_una_semilla_xmldsig_valida(): void
    {
        $this->assertTrue(
            class_exists(SiiSeedXmlSignerService::class),
            'Debe existir el firmador compartido SiiSeedXmlSignerService.'
        );

        $privateKeyPem = <<<'PEM'
-----BEGIN PRIVATE KEY-----
MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAKqu3RItkI6Cj/Qg
xEmdYIxqYkrrbNRVTIAWqOFGMW7aIR9P/9GTpt625BPNoilu1DfEMMcJyQwYLL1D
Rnap8+W3tCHOBVjCW+ztdRneUxKK9MBUPwX2H14tzhwv/Ugg2yAwxx1WgDElc1v/
DonO5P0qk0m1ByuDlLMHDbUDRu/JAgMBAAECgYBjsg/e9k5hb1G2Pw1oEky6t8kC
CdFflRNCHfo221E0dqSyLYA3Yg8uN5WxG4OEv/+lMytqlwSf098ODaWy2kJjDrck
vFixcJV1j+RS1AMcxFJihjNF6LpBXpcZmqvVUxEx2ps0NeLQU82JnWRts1pTYQbq
50Fn1JbmFv2eAv46oQJBAOD9zrzsXFmaQqqcakh/L+X/lWvP6xsOeNobUF0X1Q8h
AuZX/vc2wIrlDO4F/S98rUjQDVoGKjF8ThKHhALpXPcCQQDCNO/oL9hyxLodb9wz
1BgYXS4q5XldSQD7JuQZ6JsmA3v2FVpJFzb+MCpKxgcIiSt6ptqs/c0eGe2OXezx
1qk/AkBqfjfYnFep4aYkcxyra+gUCUGEYkl56QOy2LLVHW6vVoS02nnIMZY5J+lS
0GriizTJ/hATyE84VQnvI02Mw0BJAkAhDYVvTQVXsyfB7tHZeFWJgAJlhpy7RbuH
Az17M12EgL9OSKAPJIZViLkJ9N4pk770pwU8wA1y/BK0UkQLfO9dAkEAtQw4ULhE
4aaktuqR7lb8YmxfhCR2WJN/7qSJlQw3Oir1M9CaN18RmU6tX4zUYs0Yj7p54vaa
oCKeFCoCFRPA7A==
-----END PRIVATE KEY-----
PEM;

        $modulusBase64 =
            'qq7dEi2QjoKP9CDESZ1gjGpiSuts1FVMgBao4UYxbtohH0//0ZOm3rbkE82iKW7UN8QwxwnJDBgsvUNGdqnz5be0Ic4FWMJb7O11Gd5TEor0wFQ/BfYfXi3OHC/9SCDbIDDHHVaAMSVzW/8Oic7k/SqTSbUHK4OUswcNtQNG78k=';

        $exponentBase64 = 'AQAB';

        $signer = new SiiSeedXmlSignerService();

        $xml = $signer->sign(
            seed: '030530912644',
            privateKeyPem: $privateKeyPem,
            certificateBase64: 'VEVTVF9DRVJUSUZJQ0FURQ==',
            modulusBase64: $modulusBase64,
            exponentBase64: $exponentBase64
        );

        $document = new DOMDocument();

        $this->assertTrue(
            $document->loadXML($xml)
        );

        $xpath = new DOMXPath($document);

        $xpath->registerNamespace(
            'ds',
            'http://www.w3.org/2000/09/xmldsig#'
        );

        $this->assertSame(
            'l2s9BqLppHaWo+w1Al1J5SsYScs=',
            trim(
                (string) $xpath->evaluate(
                    'string(//ds:DigestValue)'
                )
            )
        );

        $this->assertNotSame(
            '',
            trim(
                (string) $xpath->evaluate(
                    'string(//ds:SignatureValue)'
                )
            )
        );

        $this->assertSame(
            'AQAB',
            trim(
                (string) $xpath->evaluate(
                    'string(//ds:RSAKeyValue/ds:Exponent)'
                )
            )
        );
    }
}