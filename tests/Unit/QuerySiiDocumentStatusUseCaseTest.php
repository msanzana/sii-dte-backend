<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\UseCases\Document\QuerySiiDocumentStatusUseCase;
use App\Modules\Dte\Infrastructure\Sii\SiiSoapAuthenticationService;
use App\Modules\Dte\Infrastructure\Sii\SiiTokenProviderService;
use ReflectionClass;
use Tests\TestCase;
use App\Modules\Dte\Application\Services\RecalculateCafCountersService;
use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailEventRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioStatusRepositoryInterface;
use ReflectionMethod;
use App\Modules\Dte\Infrastructure\Sii\SiiBoletaTokenProviderService;
final class QuerySiiDocumentStatusUseCaseTest extends TestCase
{
    public function test_la_familia_factura_usa_el_proveedor_compartido_de_token(): void
    {
        $reflection = new ReflectionClass(
            QuerySiiDocumentStatusUseCase::class
        );

        $constructor = $reflection->getConstructor();

        $parameterTypes = array_map(
            static fn ($parameter) => $parameter->getType()?->getName(),
            $constructor?->getParameters() ?? []
        );

        $this->assertContains(
            SiiTokenProviderService::class,
            $parameterTypes
        );

        $this->assertNotContains(
            SiiSoapAuthenticationService::class,
            $parameterTypes
        );

        $source = file_get_contents(
            app_path(
                'Modules/Dte/Application/UseCases/Document/QuerySiiDocumentStatusUseCase.php'
            )
        );

        $this->assertMatchesRegularExpression(
            '/\$this->siiTokenProviderService\s*->\s*get\s*\(/',
            $source
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\$this->siiSoapAuthenticationService\s*->\s*authenticate\s*\(/',
            $source
        );
    }

    public function test_al_aceptar_un_dte_query_est_dte_sincroniza_el_folio_con_accepted_by_sii(): void
    {
        $reflection = new ReflectionClass(
            QuerySiiDocumentStatusUseCase::class
        );

        $constructor = $reflection->getConstructor();

        $parameterTypes = array_map(
            static fn ($parameter) => $parameter->getType()?->getName(),
            $constructor?->getParameters() ?? []
        );

        $this->assertContains(
            FolioDetailRepositoryInterface::class,
            $parameterTypes
        );

        $this->assertContains(
            FolioStatusRepositoryInterface::class,
            $parameterTypes
        );

        $this->assertContains(
            FolioDetailEventRepositoryInterface::class,
            $parameterTypes
        );

        $this->assertContains(
            RecalculateCafCountersService::class,
            $parameterTypes
        );

        $source = file_get_contents(
            app_path(
                'Modules/Dte/Application/UseCases/Document/QuerySiiDocumentStatusUseCase.php'
            )
        );

        $this->assertMatchesRegularExpression(
            '/findByDocumentIdForUpdate\s*\(/',
            $source
        );

        $this->assertMatchesRegularExpression(
            '/findByCode\s*\(\s*[\'"]accepted_by_sii[\'"]\s*\)/',
            $source
        );

        $this->assertMatchesRegularExpression(
            '/updateReservationState\s*\(/',
            $source
        );

        $this->assertMatchesRegularExpression(
            '/\$this->folioDetailEventRepository\s*->\s*create\s*\(/',
            $source
        );

        $this->assertMatchesRegularExpression(
            '/toStatusCode\s*:\s*[\'"]accepted_by_sii[\'"]/',
            $source
        );

        $this->assertMatchesRegularExpression(
            '/\$this->recalculateCafCountersService\s*->\s*execute\s*\(/',
            $source
        );
    }
    public function test_fan_marca_el_folio_como_cancelled_y_registra_la_conciliacion_sii(): void
    {
        $source = file_get_contents(
            app_path(
                'Modules/Dte/Application/UseCases/Document/QuerySiiDocumentStatusUseCase.php'
            )
        );

        $this->assertMatchesRegularExpression(
            '/findByCode\s*\(\s*[\'"]cancelled[\'"]\s*\)/',
            $source
        );

        $this->assertMatchesRegularExpression(
            '/eventCode\s*:\s*[\'"]cancelled_by_sii_reconciliation[\'"]/',
            $source
        );

        $this->assertMatchesRegularExpression(
            '/toStatusCode\s*:\s*[\'"]cancelled[\'"]/',
            $source
        );

        $this->assertMatchesRegularExpression(
            '/usedAt\s*:\s*null/',
            $source
        );

        $this->assertMatchesRegularExpression(
            '/\$this->recalculateCafCountersService\s*->\s*execute\s*\(/',
            $source
        );
    }
    public function test_accepted_with_reparos_tambien_sincroniza_el_folio_como_accepted_by_sii(): void
    {
        $source = file_get_contents(
            app_path(
                'Modules/Dte/Application/UseCases/Document/QuerySiiDocumentStatusUseCase.php'
            )
        );

        $this->assertMatchesRegularExpression(
            '/in_array\s*\(\s*\$document->status\(\)\s*,\s*\[\s*[\'"]accepted[\'"]\s*,\s*[\'"]accepted_with_reparos[\'"]\s*\]\s*,\s*true\s*\)/s',
            $source
        );

        $this->assertMatchesRegularExpression(
            '/findByCode\s*\(\s*[\'"]accepted_by_sii[\'"]\s*\)/',
            $source
        );

        $this->assertMatchesRegularExpression(
            '/eventCode\s*:\s*[\'"]accepted_by_sii[\'"]/',
            $source
        );

        $this->assertMatchesRegularExpression(
            '/toStatusCode\s*:\s*[\'"]accepted_by_sii[\'"]/',
            $source
        );
    }
    public function test_mmc_se_mapea_como_accepted_with_reparos(): void
    {
        $reflection = new \ReflectionClass(
            \App\Modules\Dte\Application\UseCases\Document\QuerySiiDocumentStatusUseCase::class
        );

        $useCase = $reflection->newInstanceWithoutConstructor();

        $method = $reflection->getMethod(
            'mapFacturaResultToDocument'
        );

        $method->setAccessible(true);

        $document = new class {
            public ?string $mappedTo = null;
            public ?string $mappedCode = null;
            public ?string $mappedMessage = null;

            public function withAcceptedStatus(): self
            {
                $clone = clone $this;
                $clone->mappedTo = 'accepted';

                return $clone;
            }

            public function withAcceptedWithReparosStatus(
                ?string $code = null,
                ?string $message = null
            ): self {
                $clone = clone $this;
                $clone->mappedTo = 'accepted_with_reparos';
                $clone->mappedCode = $code;
                $clone->mappedMessage = $message;

                return $clone;
            }

            public function withRejectedStatus(
                ?string $code = null,
                ?string $message = null
            ): self {
                $clone = clone $this;
                $clone->mappedTo = 'rejected';
                $clone->mappedCode = $code;
                $clone->mappedMessage = $message;

                return $clone;
            }
        };

        $result = $method->invoke(
            $useCase,
            $document,
            'MMC',
            'Existe Nota de Crédito que Modifica Montos Documento'
        );

        $this->assertSame(
            'accepted_with_reparos',
            $result->mappedTo
        );

        $this->assertSame(
            'MMC',
            $result->mappedCode
        );

        $this->assertSame(
            'Existe Nota de Crédito que Modifica Montos Documento',
            $result->mappedMessage
        );
    }
    public function test_and_y_anc_se_mapean_como_accepted_with_reparos(): void
    {
        $reflection = new \ReflectionClass(
            \App\Modules\Dte\Application\UseCases\Document\QuerySiiDocumentStatusUseCase::class
        );

        $useCase = $reflection->newInstanceWithoutConstructor();

        $method = $reflection->getMethod(
            'mapFacturaResultToDocument'
        );

        $method->setAccessible(true);

        foreach ([
            ['AND', 'Existe Nota de Débito que Anula Documento'],
            ['ANC', 'Existe Nota de Crédito que Anula Documento'],
        ] as [$code, $message]) {

            $document = new class {
                public ?string $mappedTo = null;
                public ?string $mappedCode = null;
                public ?string $mappedMessage = null;

                public function withAcceptedStatus(): self
                {
                    $clone = clone $this;
                    $clone->mappedTo = 'accepted';

                    return $clone;
                }

                public function withAcceptedWithReparosStatus(
                    ?string $code = null,
                    ?string $message = null
                ): self {
                    $clone = clone $this;
                    $clone->mappedTo = 'accepted_with_reparos';
                    $clone->mappedCode = $code;
                    $clone->mappedMessage = $message;

                    return $clone;
                }

                public function withRejectedStatus(
                    ?string $code = null,
                    ?string $message = null
                ): self {
                    $clone = clone $this;
                    $clone->mappedTo = 'rejected';
                    $clone->mappedCode = $code;
                    $clone->mappedMessage = $message;

                    return $clone;
                }
            };

            $result = $method->invoke(
                $useCase,
                $document,
                $code,
                $message
            );

            $this->assertSame(
                'accepted_with_reparos',
                $result->mappedTo
            );

            $this->assertSame(
                $code,
                $result->mappedCode
            );

            $this->assertSame(
                $message,
                $result->mappedMessage
            );
        }
    }
    public function test_fau_fna_y_emp_no_modifican_el_folio_en_la_reconciliacion(): void
    {
        $reflection = new \ReflectionClass(
            \App\Modules\Dte\Application\UseCases\Document\QuerySiiDocumentStatusUseCase::class
        );

        $useCase = $reflection->newInstanceWithoutConstructor();

        $method = $reflection->getMethod(
            'syncCancelledFolioWithSii'
        );

        $method->setAccessible(true);

        foreach (['FAU', 'FNA', 'EMP'] as $code) {
            try {
                $method->invoke(
                    $useCase,
                    new \stdClass(),
                    $code,
                    'Mensaje de prueba SII',
                    null
                );
            } catch (\Throwable $exception) {
                $this->fail(
                    "El código {$code} no debe intentar modificar el folio: "
                    . $exception->getMessage()
                );
            }

            $this->addToAssertionCount(1);
        }
    }
    public function test_fau_mantiene_el_documento_sent_para_seguir_consultando_sii(): void
    {
        $reflection = new \ReflectionClass(
            \App\Modules\Dte\Application\UseCases\Document\QuerySiiDocumentStatusUseCase::class
        );

        $useCase = $reflection->newInstanceWithoutConstructor();

        $method = $reflection->getMethod(
            'mapFacturaResultToDocument'
        );

        $method->setAccessible(true);

        $document = new class {
            public ?string $mappedTo = null;
            public ?string $mappedCode = null;
            public ?string $mappedMessage = null;

            public function withAcceptedStatus(): self
            {
                $clone = clone $this;
                $clone->mappedTo = 'accepted';

                return $clone;
            }

            public function withAcceptedWithReparosStatus(
                ?string $code = null,
                ?string $message = null
            ): self {
                $clone = clone $this;
                $clone->mappedTo = 'accepted_with_reparos';
                $clone->mappedCode = $code;
                $clone->mappedMessage = $message;

                return $clone;
            }

            public function withSentStatus(
                ?string $code = null,
                ?string $message = null
            ): self {
                $clone = clone $this;
                $clone->mappedTo = 'sent';
                $clone->mappedCode = $code;
                $clone->mappedMessage = $message;

                return $clone;
            }

            public function withRejectedStatus(
                ?string $code = null,
                ?string $message = null
            ): self {
                $clone = clone $this;
                $clone->mappedTo = 'rejected';
                $clone->mappedCode = $code;
                $clone->mappedMessage = $message;

                return $clone;
            }
        };

        $result = $method->invoke(
            $useCase,
            $document,
            'FAU',
            'Documento No Recibido por el SII'
        );

        $this->assertSame(
            'sent',
            $result->mappedTo
        );

        $this->assertSame(
            'FAU',
            $result->mappedCode
        );

        $this->assertSame(
            'Documento No Recibido por el SII',
            $result->mappedMessage
        );
    }
    public function test_with_sent_status_permite_guardar_el_diagnostico_sii(): void
    {
        $method = new \ReflectionMethod(
            \App\Modules\Dte\Domain\Entities\DteDocument::class,
            'withSentStatus'
        );

        $this->assertSame(
            2,
            $method->getNumberOfParameters()
        );

        $this->assertSame(
            0,
            $method->getNumberOfRequiredParameters()
        );

        $source = file_get_contents(
            app_path(
                'Modules/Dte/Domain/Entities/DteDocument.php'
            )
        );

        $this->assertMatchesRegularExpression(
            '/public function withSentStatus\s*\(\s*\?string \$code = null\s*,\s*\?string \$message = null\s*\)/s',
            $source
        );

        $this->assertMatchesRegularExpression(
            '/lastErrorCode\s*:\s*\$code/',
            $source
        );

        $this->assertMatchesRegularExpression(
            '/lastErrorMessage\s*:\s*\$message/',
            $source
        );
    }
    public function test_fna_y_emp_quedan_rejected_y_no_se_tratan_como_reenvio(): void
    {
        $reflection = new \ReflectionClass(
            \App\Modules\Dte\Application\UseCases\Document\QuerySiiDocumentStatusUseCase::class
        );

        $useCase = $reflection->newInstanceWithoutConstructor();

        $method = $reflection->getMethod(
            'mapFacturaResultToDocument'
        );

        $method->setAccessible(true);

        foreach ([
            ['FNA', 'Documento No Autorizado'],
            ['EMP', 'Empresa No Autorizada a Emitir Documentos Tributarios Electrónicos'],
        ] as [$code, $message]) {

            $document = new class {
                public ?string $mappedTo = null;
                public ?string $mappedCode = null;
                public ?string $mappedMessage = null;

                public function withAcceptedStatus(): self
                {
                    $clone = clone $this;
                    $clone->mappedTo = 'accepted';

                    return $clone;
                }

                public function withAcceptedWithReparosStatus(
                    ?string $code = null,
                    ?string $message = null
                ): self {
                    $clone = clone $this;
                    $clone->mappedTo = 'accepted_with_reparos';
                    $clone->mappedCode = $code;
                    $clone->mappedMessage = $message;

                    return $clone;
                }

                public function withSentStatus(
                    ?string $code = null,
                    ?string $message = null
                ): self {
                    $clone = clone $this;
                    $clone->mappedTo = 'sent';
                    $clone->mappedCode = $code;
                    $clone->mappedMessage = $message;

                    return $clone;
                }

                public function withRejectedStatus(
                    ?string $code = null,
                    ?string $message = null
                ): self {
                    $clone = clone $this;
                    $clone->mappedTo = 'rejected';
                    $clone->mappedCode = $code;
                    $clone->mappedMessage = $message;

                    return $clone;
                }
            };

            $result = $method->invoke(
                $useCase,
                $document,
                $code,
                $message
            );

            $this->assertSame(
                'rejected',
                $result->mappedTo
            );

            $this->assertSame(
                $code,
                $result->mappedCode
            );

            $this->assertSame(
                $message,
                $result->mappedMessage
            );
        }
    }
    public function test_la_familia_boleta_sincroniza_el_folio_cuando_el_documento_es_aceptado(): void
    {
        $source = file_get_contents(
            app_path(
                'Modules/Dte/Application/UseCases/Document/QuerySiiDocumentStatusUseCase.php'
            )
        );

        $this->assertMatchesRegularExpression(
            '/private function QueryBoletaFamily\s*\(.*?\$savedDocument\s*=\s*\$this->documentRepository->update\s*\(\s*\$updatedDocument\s*\)\s*;\s*\$this->syncAcceptedFolioWithSii\s*\(\s*document\s*:\s*\$savedDocument\s*\)\s*;/s',
            $source
        );
    }
    public function test_la_respuesta_de_boleta_usa_status_message_como_mensaje_sii(): void
    {
        $source = file_get_contents(
            app_path(
                'Modules/Dte/Application/UseCases/Document/QuerySiiDocumentStatusUseCase.php'
            )
        );

        $this->assertMatchesRegularExpression(
            '/private function QueryBoletaFamily\s*\(.*?siiStatusCode\s*:\s*\$result\[[\'"]status_code[\'"]\]\s*,\s*siiStatusMessage\s*:\s*\$result\[[\'"]status_message[\'"]\]/s',
            $source
        );
    }
    public function test_dok_de_boleta_se_mapea_como_accepted(): void
    {
        $document = new class {
            public bool $accepted = false;

            public function withAcceptedStatus()
            {
                $this->accepted = true;

                return $this;
            }

            public function withAcceptedWithReparosStatus(
                ?string $code = null,
                ?string $message = null
            ) {
                return $this;
            }

            public function withRejectedStatus(
                ?string $code = null,
                ?string $message = null
            ) {
                return $this;
            }
        };

        $reflection = new ReflectionClass(
            QuerySiiDocumentStatusUseCase::class
        );

        $useCase = $reflection->newInstanceWithoutConstructor();

        $method = $reflection->getMethod(
            'mapBoletaResultToDocument'
        );

        $method->setAccessible(true);

        $result = $method->invoke(
            $useCase,
            $document,
            'DOK',
            'Documento Recibido por el SII. Datos Coinciden con los Registrados'
        );

        $this->assertTrue($result->accepted);
    }
    public function test_los_estados_de_boleta_con_observaciones_se_mapean_como_accepted_with_reparos(): void
    {
        $codes = [
            'DNK',
            'TMD',
            'TMC',
            'MMD',
            'MMC',
            'AND',
            'ANC',
        ];

        $reflection = new ReflectionClass(
            QuerySiiDocumentStatusUseCase::class
        );

        $useCase = $reflection->newInstanceWithoutConstructor();

        $method = $reflection->getMethod(
            'mapBoletaResultToDocument'
        );

        $method->setAccessible(true);

        foreach ($codes as $code) {
            $document = new class {
                public bool $acceptedWithReparos = false;
                public ?string $code = null;
                public ?string $message = null;

                public function withAcceptedStatus()
                {
                    return $this;
                }

                public function withAcceptedWithReparosStatus(
                    ?string $code = null,
                    ?string $message = null
                ) {
                    $this->acceptedWithReparos = true;
                    $this->code = $code;
                    $this->message = $message;

                    return $this;
                }

                public function withRejectedStatus(
                    ?string $code = null,
                    ?string $message = null
                ) {
                    return $this;
                }
            };

            $result = $method->invoke(
                $useCase,
                $document,
                $code,
                'Estado informado por el SII'
            );

            $this->assertTrue(
                $result->acceptedWithReparos,
                "El código {$code} debe mapearse como accepted_with_reparos."
            );

            $this->assertSame(
                $code,
                $result->code
            );

            $this->assertSame(
                'Estado informado por el SII',
                $result->message
            );
        }
    }
    public function test_fau_de_boleta_mantiene_el_documento_sent_y_guarda_el_diagnostico_sii(): void
    {
        $document = new class {
            public bool $sent = false;
            public ?string $code = null;
            public ?string $message = null;

            public function withAcceptedStatus()
            {
                return $this;
            }

            public function withAcceptedWithReparosStatus(
                ?string $code = null,
                ?string $message = null
            ) {
                return $this;
            }

            public function withRejectedStatus(
                ?string $code = null,
                ?string $message = null
            ) {
                return $this;
            }

            public function withSentStatus(
                ?string $code = null,
                ?string $message = null
            ) {
                $this->sent = true;
                $this->code = $code;
                $this->message = $message;

                return $this;
            }
        };

        $reflection = new ReflectionClass(
            QuerySiiDocumentStatusUseCase::class
        );

        $useCase = $reflection->newInstanceWithoutConstructor();

        $method = $reflection->getMethod(
            'mapBoletaResultToDocument'
        );

        $method->setAccessible(true);

        $result = $method->invoke(
            $useCase,
            $document,
            'FAU',
            'Documento No Recibido por el SII'
        );

        $this->assertTrue(
            $result->sent
        );

        $this->assertSame(
            'FAU',
            $result->code
        );

        $this->assertSame(
            'Documento No Recibido por el SII',
            $result->message
        );
    }
    public function test_fan_fna_y_emp_de_boleta_se_mapean_como_rejected(): void
    {
        $codes = [
            'FAN',
            'FNA',
            'EMP',
        ];

        $reflection = new ReflectionClass(
            QuerySiiDocumentStatusUseCase::class
        );

        $useCase = $reflection->newInstanceWithoutConstructor();

        $method = $reflection->getMethod(
            'mapBoletaResultToDocument'
        );

        $method->setAccessible(true);

        foreach ($codes as $code) {
            $document = new class {
                public bool $rejected = false;
                public ?string $code = null;
                public ?string $message = null;

                public function withAcceptedStatus()
                {
                    return $this;
                }

                public function withAcceptedWithReparosStatus(
                    ?string $code = null,
                    ?string $message = null
                ) {
                    return $this;
                }

                public function withSentStatus(
                    ?string $code = null,
                    ?string $message = null
                ) {
                    return $this;
                }

                public function withRejectedStatus(
                    ?string $code = null,
                    ?string $message = null
                ) {
                    $this->rejected = true;
                    $this->code = $code;
                    $this->message = $message;

                    return $this;
                }
            };

            $result = $method->invoke(
                $useCase,
                $document,
                $code,
                'Estado informado por el SII'
            );

            $this->assertTrue(
                $result->rejected,
                "El código {$code} debe mapearse como rejected."
            );

            $this->assertSame(
                $code,
                $result->code
            );

            $this->assertSame(
                'Estado informado por el SII',
                $result->message
            );
        }
    }
    public function test_la_familia_boleta_ejecuta_la_conciliacion_del_folio_para_fan(): void
    {
        $source = file_get_contents(
            app_path(
                'Modules/Dte/Application/UseCases/Document/QuerySiiDocumentStatusUseCase.php'
            )
        );

        $this->assertMatchesRegularExpression(
            '/private function QueryBoletaFamily\s*\(.*?'
            . '\$savedDocument\s*=\s*\$this->documentRepository->update\s*'
            . '\(\s*\$updatedDocument\s*\)\s*;\s*'
            . '\$this->syncAcceptedFolioWithSii\s*\(\s*'
            . 'document\s*:\s*\$savedDocument\s*'
            . '\)\s*;\s*'
            . '\$this->syncCancelledFolioWithSii\s*\(\s*'
            . 'document\s*:\s*\$savedDocument\s*,\s*'
            . 'siiCode\s*:\s*\$result\[[\'"]status_code[\'"]\]\s*,\s*'
            . 'siiMessage\s*:\s*\$result\[[\'"]status_message[\'"]\]\s*'
            . '\)\s*;/s',
            $source
        );
    }
    public function test_query_boleta_envia_el_exponente_rsa_a_la_autenticacion(): void
    {
        $method = new ReflectionMethod(
            QuerySiiDocumentStatusUseCase::class,
            'QueryBoletaFamily'
        );

        $lines = file(
            $method->getFileName()
        );

        $this->assertIsArray(
            $lines
        );

        $methodSource = implode(
            '',
            array_slice(
                $lines,
                $method->getStartLine() - 1,
                $method->getEndLine() - $method->getStartLine() + 1
            )
        );

        $this->assertStringContainsString(
            'exponentBase64: $certificateContext->exponentBase64',
            $methodSource,
            'QueryBoletaFamily debe enviar exponentBase64 al servicio de autenticación de boletas.'
        );
    }
    public function test_la_familia_boleta_usa_el_proveedor_compartido_de_token(): void
    {
        $reflection = new ReflectionClass(
            QuerySiiDocumentStatusUseCase::class
        );

        $constructor = $reflection->getConstructor();

        $this->assertNotNull(
            $constructor
        );

        $constructorTypes = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof \ReflectionNamedType) {
                $constructorTypes[] = $type->getName();
            }
        }

        $this->assertContains(
            SiiBoletaTokenProviderService::class,
            $constructorTypes,
            'QuerySiiDocumentStatusUseCase debe recibir SiiBoletaTokenProviderService.'
        );

        $method = $reflection->getMethod(
            'QueryBoletaFamily'
        );

        $lines = file(
            $method->getFileName()
        );

        $this->assertIsArray(
            $lines
        );

        $methodSource = implode(
            '',
            array_slice(
                $lines,
                $method->getStartLine() - 1,
                $method->getEndLine() - $method->getStartLine() + 1
            )
        );

        $this->assertStringContainsString(
            '$this->siiBoletaTokenProviderService->get(',
            $methodSource,
            'QueryBoletaFamily debe obtener el token mediante SiiBoletaTokenProviderService.'
        );

        $this->assertStringNotContainsString(
            '$this->siiBoletaApiAuthenticationService->authenticate(',
            $methodSource,
            'QueryBoletaFamily no debe autenticarse directamente contra el SII.'
        );

        $this->assertStringContainsString(
            'certificateId: $certificateContext->certificateId',
            $methodSource
        );

        $this->assertStringContainsString(
            'companyId: $certificateContext->companyId',
            $methodSource
        );
    }
}
