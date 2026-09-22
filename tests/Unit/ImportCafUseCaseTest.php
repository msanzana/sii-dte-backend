<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\DTOs\ImportCafInputDto;
use App\Modules\Dte\Application\Services\ValidateExternalSystemAccessService;
use App\Modules\Dte\Application\UseCases\Certificate\ImportCafUseCase;
use App\Modules\Dte\Domain\Entities\Company;
use App\Modules\Dte\Domain\Entities\ExternalSystem;
use App\Modules\Dte\Domain\Exceptions\InvalidCafException;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\ExternalSystemRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiCafRepositoryInterface;
use App\Modules\Dte\Infrastructure\Crypto\SecretEncryptionService;
use App\Modules\Dte\Infrastructure\Storage\DtePrivateStorageService;
use App\Modules\Dte\Infrastructure\Xml\CafXmlParserService;
use Tests\TestCase;
use App\Modules\Dte\Domain\Entities\SiiCaf;
use Illuminate\Support\Facades\DB;
use App\Modules\Dte\Infrastructure\Crypto\SiiCafSignatureKeyResolver;
use App\Modules\Dte\Infrastructure\Crypto\SiiCafSignatureVerifier;

final class ImportCafUseCaseTest extends TestCase
{
    public function test_rechaza_un_caf_cuyo_rut_emisor_no_corresponde_a_la_empresa(): void
    {
        $company = new Company(
            id: 1,
            rut: '76123456-7',
            rutBody: '76123456',
            rutDv: '7',
            legalName: 'Empresa de prueba',
            tradeName: null,
            giro: null,
            siiActivityCode: null,
            address: 'Dirección de prueba',
            cityId: 1,
            dteEmail: null,
            resolutionNumber: null,
            resolutionDate: null,
            siiEnvironment: 'cert',
            isActive: true,
        );

        $externalSystem = new ExternalSystem(
            id: 2,
            companyId: 1,
            code: 'TEST',
            name: 'Sistema de prueba',
            description: null,
            isActive: true,
        );

        $companyRepository = $this->createMock(
            CompanyRepositoryInterface::class
        );

        $companyRepository
            ->expects($this->once())
            ->method('existsActiveById')
            ->with(1)
            ->willReturn(true);

        $companyRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($company);

        $externalSystemRepository = $this->createMock(
            ExternalSystemRepositoryInterface::class
        );

        $externalSystemRepository
            ->expects($this->once())
            ->method('findByCompanyAndId')
            ->with(1, 2)
            ->willReturn($externalSystem);

        $cafRepository = $this->createMock(
            SiiCafRepositoryInterface::class
        );

        $cafRepository
            ->expects($this->never())
            ->method('existsOverlappingRange');

        $cafRepository
            ->expects($this->never())
            ->method('create');

        $logRepository = $this->createMock(
            IntegrationLogRepositoryInterface::class
        );

        $storageService = $this->createMock(
            DtePrivateStorageService::class
        );

        $storageService
            ->expects($this->never())
            ->method('storeString');

        $secretEncryptionService = $this->createMock(
            SecretEncryptionService::class
        );

        $secretEncryptionService
            ->expects($this->never())
            ->method('encrypt');

        $validateExternalSystemAccessService =
            new ValidateExternalSystemAccessService(
                $externalSystemRepository
            );
        $cafSignatureVerifier = new SiiCafSignatureVerifier(
            new SiiCafSignatureKeyResolver()
        );
        $useCase = new ImportCafUseCase(
            companyRepository: $companyRepository,
            cafRepository: $cafRepository,
            logRepository: $logRepository,
            storageService: $storageService,
            cafXmlParserService: new CafXmlParserService(),
            secretEncryptionService: $secretEncryptionService,
            validateExternalSystemAccessService:
                $validateExternalSystemAccessService,
            cafSignatureVerifier: $cafSignatureVerifier,
        );

$xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<AUTORIZACION>
    <CAF version="1.0">
        <DA>
            <RE>11111111-1</RE>
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

        <FRMA algoritmo="SHA1withRSA">
            FIRMA-BASE64-PRUEBA
        </FRMA>
    </CAF>

    <RSASK>CLAVE-PRUEBA</RSASK>
</AUTORIZACION>
XML;

        $tempFile = tempnam(
            sys_get_temp_dir(),
            'caf_'
        );

        if ($tempFile === false) {
            $this->fail(
                'No fue posible crear el archivo temporal del test.'
            );
        }

        file_put_contents(
            $tempFile,
            $xml
        );

        $this->expectException(
            InvalidCafException::class
        );

        $this->expectExceptionMessage(
            'El RUT emisor del CAF 11111111-1 no coincide con el RUT 76123456-7 de la empresa 1.'
        );

        try {
            $useCase->execute(
                new ImportCafInputDto(
                    companyId: 1,
                    externalSystemId: 2,
                    originalFilename: 'caf-prueba.xml',
                    tempFilePath: $tempFile,
                    isActive: true,
                )
            );
        } finally {
            if (is_file($tempFile)) {
                unlink($tempFile);
            }
        }
    }
    public function test_guarda_rsapubk_como_llave_publica_pem_del_caf(): void
    {
    $siiPrivateKeyPem = <<<'PEM'
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

$siiPublicKeyPem = <<<'PEM'
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
                '100' => $siiPublicKeyPem,
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
    <IDK>100</IDK>
</DA>
XML;
        $signature = '';
        DB::shouldReceive('transaction')
        ->once()
        ->andReturnUsing(
            function (callable $callback) {
                return $callback();
            }
        );
        $signed = openssl_sign(
            $daXml,
            $signature,
            $siiPrivateKeyPem,
            OPENSSL_ALGO_SHA1
        );
        $frmaValue = base64_encode(
            $signature
        );
        $company = new Company(
            id: 1,
            rut: '76123456-7',
            rutBody: '76123456',
            rutDv: '7',
            legalName: 'Empresa de prueba',
            tradeName: null,
            giro: null,
            siiActivityCode: null,
            address: 'Dirección de prueba',
            cityId: 1,
            dteEmail: null,
            resolutionNumber: null,
            resolutionDate: null,
            siiEnvironment: 'cert',
            isActive: true,
        );

        $externalSystem = new ExternalSystem(
            id: 2,
            companyId: 1,
            code: 'TEST',
            name: 'Sistema de prueba',
            description: null,
            isActive: true,
        );

        $companyRepository = $this->createMock(
            CompanyRepositoryInterface::class
        );

        $companyRepository
            ->method('existsActiveById')
            ->with(1)
            ->willReturn(true);

        $companyRepository
            ->method('findById')
            ->with(1)
            ->willReturn($company);

        $externalSystemRepository = $this->createMock(
            ExternalSystemRepositoryInterface::class
        );

        $externalSystemRepository
            ->method('findByCompanyAndId')
            ->with(1, 2)
            ->willReturn($externalSystem);

        $cafXmlParserService = $this->createMock(
            CafXmlParserService::class
        );

        $publicKeyPem =
            "-----BEGIN PUBLIC KEY-----\n"
            . "LLAVE-PUBLICA-RSAPUBK\n"
            . "-----END PUBLIC KEY-----";

        $cafXmlParserService
            ->method('parse')
            ->willReturn([
                'issuer_rut' => '76123456-7',

                'dte_type' => 39,
                'folio_start' => 1,
                'folio_end' => 10,
                'authorized_at' => '2026-09-17',

                'sii_key_id' => '100',
                'frma_algorithm' => 'SHA1withRSA',
                'frma_value' => $frmaValue,

                'private_key_material' =>
                    '<RSASK>CLAVE-PRIVADA-PRUEBA</RSASK>',

                /*
                * RSAPK interno del CAF.
                * Este NO debe terminar almacenado como public_key_pem.
                */
                'public_key_material' =>
                    '<RSAPK><M>MODULO-PRUEBA</M><E>Aw==</E></RSAPK>',

                /*
                * RSAPUBK del archivo AUTORIZACION.
                * Este es el valor que sí esperamos persistir.
                */
                'public_key_pem' => $publicKeyPem,
                'da_xml' => $daXml,
            ]);

        $cafRepository = $this->createMock(
            SiiCafRepositoryInterface::class
        );

        $cafRepository
            ->method('existsOverlappingRange')
            ->with(
                1,
                39,
                1,
                10
            )
            ->willReturn(false);

        $savedCaf = new SiiCaf(
            id: 10,
            companyId: 1,
            dteType: 39,
            folioStart: 1,
            folioEnd: 10,
            lastAssignedFolio: null,
            cafXmlPath: 'app/private/dte/caf/caf-prueba.xml',
            privateKeyPemEncrypted: 'CLAVE-CIFRADA',
            publicKeyPem: $publicKeyPem,
            authorizedAt: '2026-09-17',
            isActive: true,
            externalSystemId: 2,
            requestedFoliosCount: 10,
            availableFoliosCount: 10,
            reservedFoliosCount: 0,
            usedFoliosCount: 0,
        );

        $cafRepository
            ->expects($this->once())
            ->method('create')
            ->with(
                $this->callback(
                    function (SiiCaf $caf) use ($publicKeyPem): bool {
                        $this->assertSame(
                            $publicKeyPem,
                            $caf->publicKeyPem()
                        );

                        $this->assertNotSame(
                            '<RSAPK><M>MODULO-PRUEBA</M><E>Aw==</E></RSAPK>',
                            $caf->publicKeyPem()
                        );

                        return true;
                    }
                )
            )
            ->willReturn($savedCaf);

        $logRepository = $this->createMock(
            IntegrationLogRepositoryInterface::class
        );

        $storageService = $this->createMock(
            DtePrivateStorageService::class
        );

        $storageService
            ->method('storeString')
            ->willReturn(
                'app/private/dte/caf/caf-prueba.xml'
            );

        $secretEncryptionService = $this->createMock(
            SecretEncryptionService::class
        );

        $secretEncryptionService
            ->method('encrypt')
            ->with(
                '<RSASK>CLAVE-PRIVADA-PRUEBA</RSASK>'
            )
            ->willReturn('CLAVE-CIFRADA');

        $validateExternalSystemAccessService =
            new ValidateExternalSystemAccessService(
                $externalSystemRepository
            );
        $cafSignatureVerifier = new SiiCafSignatureVerifier(
            new SiiCafSignatureKeyResolver()
        );
        $useCase = new ImportCafUseCase(
            companyRepository: $companyRepository,
            cafRepository: $cafRepository,
            logRepository: $logRepository,
            storageService: $storageService,
            cafXmlParserService: $cafXmlParserService,
            secretEncryptionService: $secretEncryptionService,
            validateExternalSystemAccessService:
                $validateExternalSystemAccessService,
            cafSignatureVerifier: $cafSignatureVerifier,
        );

        $tempFile = tempnam(
            sys_get_temp_dir(),
            'caf_'
        );

        if ($tempFile === false) {
            $this->fail(
                'No fue posible crear el archivo temporal del test.'
            );
        }

        file_put_contents(
            $tempFile,
            '<AUTORIZACION></AUTORIZACION>'
        );

        try {
            $useCase->execute(
                new ImportCafInputDto(
                    companyId: 1,
                    externalSystemId: 2,
                    originalFilename: 'caf-prueba.xml',
                    tempFilePath: $tempFile,
                    isActive: true,
                )
            );
        } finally {
            if (is_file($tempFile)) {
                unlink($tempFile);
            }
        }
    }
    public function test_rechaza_caf_con_frma_invalida_antes_de_persistir(): void
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

        $company = new Company(
            id: 1,
            rut: '76123456-7',
            rutBody: '76123456',
            rutDv: '7',
            legalName: 'Empresa de prueba',
            tradeName: null,
            giro: null,
            siiActivityCode: null,
            address: 'Dirección de prueba',
            cityId: 1,
            dteEmail: null,
            resolutionNumber: null,
            resolutionDate: null,
            siiEnvironment: 'cert',
            isActive: true,
        );

        $externalSystem = new ExternalSystem(
            id: 2,
            companyId: 1,
            code: 'TEST',
            name: 'Sistema de prueba',
            description: null,
            isActive: true,
        );

        $companyRepository = $this->createMock(
            CompanyRepositoryInterface::class
        );

        $companyRepository
            ->expects($this->once())
            ->method('existsActiveById')
            ->with(1)
            ->willReturn(true);

        $companyRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($company);

        $externalSystemRepository = $this->createMock(
            ExternalSystemRepositoryInterface::class
        );

        $externalSystemRepository
            ->expects($this->once())
            ->method('findByCompanyAndId')
            ->with(1, 2)
            ->willReturn($externalSystem);

        $cafRepository = $this->createMock(
            SiiCafRepositoryInterface::class
        );

        /*
        * Una FRMA inválida debe detener el proceso ANTES
        * de cualquier consulta/persistencia del CAF.
        */
        $cafRepository
            ->expects($this->never())
            ->method('existsOverlappingRange');

        $cafRepository
            ->expects($this->never())
            ->method('create');

        $logRepository = $this->createMock(
            IntegrationLogRepositoryInterface::class
        );

        $logRepository
            ->expects($this->never())
            ->method('info');

        $storageService = $this->createMock(
            DtePrivateStorageService::class
        );

        $storageService
            ->expects($this->never())
            ->method('storeString');

        $secretEncryptionService = $this->createMock(
            SecretEncryptionService::class
        );

        $secretEncryptionService
            ->expects($this->never())
            ->method('encrypt');

        $cafXmlParserService = $this->createMock(
            CafXmlParserService::class
        );

        $cafXmlParserService
            ->expects($this->once())
            ->method('parse')
            ->willReturn([
                'issuer_rut' => '76123456-7',

                'dte_type' => 39,
                'folio_start' => 1,
                'folio_end' => 10,
                'authorized_at' => '2026-09-17',

                'sii_key_id' => '100',
                'frma_algorithm' => 'SHA1withRSA',

                /*
                * Deliberadamente inválido.
                */
                'frma_value' => '***NO-ES-BASE64***',

                'da_xml' =>
                    '<DA><RE>76123456-7</RE><IDK>100</IDK></DA>',

                'private_key_material' =>
                    '<RSASK>CLAVE-PRUEBA</RSASK>',

                'public_key_material' =>
                    '<RSAPK><M>MODULO</M><E>Aw==</E></RSAPK>',

                'public_key_pem' => $publicKeyPem,
            ]);

        $validateExternalSystemAccessService =
            new ValidateExternalSystemAccessService(
                $externalSystemRepository
            );

        $resolver =
            new SiiCafSignatureKeyResolver();

        $cafSignatureVerifier =
            new SiiCafSignatureVerifier(
                $resolver
            );

        $tempFile = tempnam(
            sys_get_temp_dir(),
            'caf_'
        );

        if ($tempFile === false) {
            $this->fail(
                'No fue posible crear el archivo temporal del test.'
            );
        }

        file_put_contents(
            $tempFile,
            '<AUTORIZACION></AUTORIZACION>'
        );

        try {
            $useCase = new ImportCafUseCase(
                companyRepository: $companyRepository,
                cafRepository: $cafRepository,
                logRepository: $logRepository,
                storageService: $storageService,
                cafXmlParserService: $cafXmlParserService,
                secretEncryptionService: $secretEncryptionService,
                validateExternalSystemAccessService:
                    $validateExternalSystemAccessService,

                /*
                * Esta dependencia todavía no existe
                * en ImportCafUseCase.
                */
                cafSignatureVerifier: $cafSignatureVerifier,
            );

            $this->expectException(
                InvalidCafException::class
            );

            $this->expectExceptionMessage(
                'La firma FRMA del CAF no contiene un Base64 válido.'
            );

            $useCase->execute(
                new ImportCafInputDto(
                    companyId: 1,
                    externalSystemId: 2,
                    originalFilename: 'caf-frma-invalida.xml',
                    tempFilePath: $tempFile,
                    isActive: true,
                )
            );
        } finally {
            if (is_file($tempFile)) {
                unlink($tempFile);
            }
        }
    }
    public function test_rechaza_caf_con_frma_criptograficamente_invalida_antes_de_persistir(): void
    {
        /*
        * Estas dos llaves son exclusivamente de prueba.
        *
        * Simulan el par utilizado por el SII:
        *
        * privada -> genera FRMA
        * pública -> IDK 100 permite verificar FRMA
        */
    $siiPrivateKeyPem = <<<'PEM'
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

    $siiPublicKeyPem = <<<'PEM'
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
                '100' => $siiPublicKeyPem,
            ],
        ]);

        $company = new Company(
            id: 1,
            rut: '76123456-7',
            rutBody: '76123456',
            rutDv: '7',
            legalName: 'Empresa de prueba',
            tradeName: null,
            giro: null,
            siiActivityCode: null,
            address: 'Dirección de prueba',
            cityId: 1,
            dteEmail: null,
            resolutionNumber: null,
            resolutionDate: null,
            siiEnvironment: 'cert',
            isActive: true,
        );

        $externalSystem = new ExternalSystem(
            id: 2,
            companyId: 1,
            code: 'TEST',
            name: 'Sistema de prueba',
            description: null,
            isActive: true,
        );

        $companyRepository = $this->createMock(
            CompanyRepositoryInterface::class
        );

        $companyRepository
            ->expects($this->once())
            ->method('existsActiveById')
            ->with(1)
            ->willReturn(true);

        $companyRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($company);

        $externalSystemRepository = $this->createMock(
            ExternalSystemRepositoryInterface::class
        );

        $externalSystemRepository
            ->expects($this->once())
            ->method('findByCompanyAndId')
            ->with(1, 2)
            ->willReturn($externalSystem);

        /*
        * El DA es perfectamente válido.
        */
        $daXml = <<<'XML'
<DA>
    <RE>76123456-7</RE>
    <TD>39</TD>
    <RNG>
        <D>1</D>
        <H>10</H>
    </RNG>
    <IDK>100</IDK>
</DA>
XML;

        /*
        * Primero generamos una firma auténtica.
        */
        $signature = '';

        $signed = openssl_sign(
            $daXml,
            $signature,
            $siiPrivateKeyPem,
            OPENSSL_ALGO_SHA1
        );

        if ($signed !== true) {
            $this->fail(
                'No fue posible generar la firma RSA-SHA1 del CAF de prueba.'
            );
        }

        /*
        * AHORA alteramos UN byte.
        *
        * La longitud de la firma se mantiene.
        * El Base64 seguirá siendo perfectamente válido.
        *
        * Pero criptográficamente la firma ya no corresponde al DA.
        */
        $signature[0] = chr(
            ord($signature[0]) ^ 0x01
        );

        $manipulatedFrmaValue = base64_encode(
            $signature
        );

        $cafXmlParserService = $this->createMock(
            CafXmlParserService::class
        );

        $cafXmlParserService
            ->expects($this->once())
            ->method('parse')
            ->willReturn([
                'issuer_rut' => '76123456-7',

                'dte_type' => 39,
                'folio_start' => 1,
                'folio_end' => 10,
                'authorized_at' => '2026-09-17',

                'sii_key_id' => '100',
                'frma_algorithm' => 'SHA1withRSA',

                /*
                * Es Base64 válido, pero RSA inválido.
                */
                'frma_value' => $manipulatedFrmaValue,

                'da_xml' => $daXml,

                'private_key_material' =>
                    '<RSASK>CLAVE-PRUEBA</RSASK>',

                'public_key_material' =>
                    '<RSAPK><M>MODULO</M><E>Aw==</E></RSAPK>',

                'public_key_pem' =>
                    "-----BEGIN PUBLIC KEY-----\n"
                    . "RSAPUBK-PRUEBA\n"
                    . "-----END PUBLIC KEY-----",
            ]);

        /*
        * Si FRMA falla criptográficamente, nunca debemos llegar
        * a ninguna de estas operaciones.
        */
        $cafRepository = $this->createMock(
            SiiCafRepositoryInterface::class
        );

        $cafRepository
            ->expects($this->never())
            ->method('existsOverlappingRange');

        $cafRepository
            ->expects($this->never())
            ->method('create');

        $logRepository = $this->createMock(
            IntegrationLogRepositoryInterface::class
        );

        $logRepository
            ->expects($this->never())
            ->method('info');

        $storageService = $this->createMock(
            DtePrivateStorageService::class
        );

        $storageService
            ->expects($this->never())
            ->method('storeString');

        $secretEncryptionService = $this->createMock(
            SecretEncryptionService::class
        );

        $secretEncryptionService
            ->expects($this->never())
            ->method('encrypt');

        $validateExternalSystemAccessService =
            new ValidateExternalSystemAccessService(
                $externalSystemRepository
            );

        $cafSignatureVerifier =
            new SiiCafSignatureVerifier(
                new SiiCafSignatureKeyResolver()
            );

        $tempFile = tempnam(
            sys_get_temp_dir(),
            'caf_'
        );

        if ($tempFile === false) {
            $this->fail(
                'No fue posible crear el archivo temporal del test.'
            );
        }

        file_put_contents(
            $tempFile,
            '<AUTORIZACION></AUTORIZACION>'
        );

        try {
            $useCase = new ImportCafUseCase(
                companyRepository: $companyRepository,
                cafRepository: $cafRepository,
                logRepository: $logRepository,
                storageService: $storageService,
                cafXmlParserService: $cafXmlParserService,
                secretEncryptionService: $secretEncryptionService,
                validateExternalSystemAccessService:
                    $validateExternalSystemAccessService,
                cafSignatureVerifier: $cafSignatureVerifier,
            );

            $this->expectException(
                InvalidCafException::class
            );

            $this->expectExceptionMessage(
                'La firma FRMA del CAF no es válida.'
            );

            $useCase->execute(
                new ImportCafInputDto(
                    companyId: 1,
                    externalSystemId: 2,
                    originalFilename:
                        'caf-frma-criptograficamente-invalida.xml',
                    tempFilePath: $tempFile,
                    isActive: true,
                )
            );
        } finally {
            if (is_file($tempFile)) {
                unlink($tempFile);
            }
        }
    }
}