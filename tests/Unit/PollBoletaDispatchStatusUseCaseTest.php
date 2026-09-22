<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\UseCases\Dispatch\PollBoletaDispatchStatusUseCase;
use ReflectionMethod;
use Tests\TestCase;
use App\Modules\Dte\Infrastructure\Sii\SiiBoletaTokenProviderService;
use ReflectionClass;

final class PollBoletaDispatchStatusUseCaseTest extends TestCase
{
    public function test_el_polling_de_boleta_entrega_el_exponente_rsa_a_la_autenticacion(): void
    {
        $method = new ReflectionMethod(
            PollBoletaDispatchStatusUseCase::class,
            'execute'
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
            'PollBoletaDispatchStatusUseCase debe enviar exponentBase64 al servicio de autenticación de boletas.'
        );
    }
    public function test_processed_at_solo_se_asigna_a_estados_terminales_usando_comparacion_estricta(): void
    {
        $method = new ReflectionMethod(
            PollBoletaDispatchStatusUseCase::class,
            'execute'
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

        $this->assertMatchesRegularExpression(
            "/in_array\\(\\s*\\\$internalStatus\\s*,\\s*\\[\\s*['\"]processed['\"]\\s*,\\s*['\"]rejected['\"]\\s*,?\\s*\\]\\s*,\\s*true\\s*\\)/",
            $methodSource,
            'processedAt debe considerar solamente processed y rejected usando in_array en modo estricto.'
        );
    }
    public function test_un_dispatch_rejected_guarda_el_mensaje_de_error_del_sii(): void
    {
        $method = new ReflectionMethod(
            PollBoletaDispatchStatusUseCase::class,
            'execute'
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
            "\$internalStatus === 'rejected'",
            $methodSource,
            'Un dispatch rejected debe conservar el mensaje del SII en errorMessage.'
        );

        $this->assertStringNotContainsString(
            "\$internalStatus === 'regected'",
            $methodSource,
            'No debe existir el typo regected.'
        );
    }
    public function test_el_polling_de_boleta_usa_el_proveedor_compartido_de_token(): void
    {
        $reflection = new ReflectionClass(
            PollBoletaDispatchStatusUseCase::class
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
            'PollBoletaDispatchStatusUseCase debe recibir SiiBoletaTokenProviderService.'
        );

        $method = $reflection->getMethod(
            'execute'
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
            'El polling de boleta debe obtener el token mediante SiiBoletaTokenProviderService.'
        );

        $this->assertStringNotContainsString(
            '$this->siiBoletaApiAuthenticationService->authenticate(',
            $methodSource,
            'El polling de boleta no debe autenticarse directamente contra el SII.'
        );

        $this->assertStringContainsString(
            'companyId: $certificateContext->companyId',
            $methodSource
        );

        $this->assertStringContainsString(
            'certificateId: $certificateContext->certificateId',
            $methodSource
        );
    }
    public function test_el_polling_de_boleta_entrega_el_rut_de_la_empresa_a_la_consulta_del_envio(): void
    {
        $method = new ReflectionMethod(
            PollBoletaDispatchStatusUseCase::class,
            'execute'
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

        $this->assertMatchesRegularExpression(
            '/\[\s*\$companyRutBody\s*,\s*\$companyRutDv\s*\]\s*=\s*\$this->splitRut\(\s*\$company->rut\(\)\s*\);/',
            $methodSource,
            'El polling debe separar el RUT de la empresa antes de consultar el estado del envío.'
        );

        $this->assertStringContainsString(
            'rutBody: $companyRutBody',
            $methodSource,
            'El polling debe entregar el cuerpo del RUT al servicio de estado del envío.'
        );

        $this->assertStringContainsString(
            'rutDv: $companyRutDv',
            $methodSource,
            'El polling debe entregar el DV del RUT al servicio de estado del envío.'
        );
    }
    public function test_el_token_y_la_consulta_sii_se_ejecutan_antes_de_abrir_la_transaccion_de_persistencia(): void
    {
        $method = new ReflectionMethod(
            PollBoletaDispatchStatusUseCase::class,
            'execute'
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

        $tokenPosition = strpos(
            $methodSource,
            '$this->siiBoletaTokenProviderService->get('
        );

        $queryPosition = strpos(
            $methodSource,
            '$this->siiBoletaApiSendStatusService->query('
        );

        $transactionPosition = strpos(
            $methodSource,
            'DB::transaction('
        );

        $this->assertIsInt(
            $tokenPosition
        );

        $this->assertIsInt(
            $queryPosition
        );

        $this->assertIsInt(
            $transactionPosition
        );

        $this->assertLessThan(
            $queryPosition,
            $tokenPosition,
            'La obtención del TOKEN debe ocurrir antes de consultar el estado al SII.'
        );

        $this->assertLessThan(
            $transactionPosition,
            $tokenPosition,
            'La obtención del TOKEN no debe ocurrir dentro de una transacción de base de datos.'
        );

        $this->assertLessThan(
            $transactionPosition,
            $queryPosition,
            'La consulta HTTP al SII no debe ocurrir dentro de una transacción de base de datos.'
        );
    }
    public function test_la_fase_de_persistencia_bloquea_y_recarga_el_dispatch_antes_de_actualizarlo(): void
    {
        $method = new ReflectionMethod(
            PollBoletaDispatchStatusUseCase::class,
            'execute'
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

        $compactSource = preg_replace(
            '/\s+/',
            '',
            $methodSource
        );

        $this->assertIsString(
            $compactSource
        );

        $transactionPosition = strpos(
            $compactSource,
            'DB::transaction('
        );

        $lockPosition = strpos(
            $compactSource,
            '$this->dispatchRepository->findByIdForUpdate('
        );

        $this->assertIsInt(
            $transactionPosition
        );

        $this->assertIsInt(
            $lockPosition
        );

        $this->assertGreaterThan(
            $transactionPosition,
            $lockPosition,
            'El dispatch debe bloquearse dentro de la transacción de persistencia.'
        );

        $this->assertStringContainsString(
            '$lockedDispatch=$this->dispatchRepository->findByIdForUpdate((int)$dispatch->id());',
            $compactSource,
            'La FASE 3 debe volver a cargar el dispatch actual mediante findByIdForUpdate().'
        );

        $this->assertStringContainsString(
            '$lockedDispatch->withPollingResult(',
            $compactSource,
            'El resultado del polling debe aplicarse sobre el dispatch bloqueado y actualizado.'
        );

        $this->assertStringNotContainsString(
            '$updateDispatch=$dispatch->withPollingResult(',
            $compactSource,
            'La FASE 3 no debe persistir el resultado sobre la instancia antigua del dispatch.'
        );
    }
    public function test_la_fase_de_persistencia_revalida_el_dispatch_bloqueado_antes_de_guardar_el_resultado(): void
    {
        $method = new ReflectionMethod(
            PollBoletaDispatchStatusUseCase::class,
            'execute'
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

        $compactSource = preg_replace(
            '/\s+/',
            '',
            $methodSource
        );

        $this->assertIsString(
            $compactSource
        );

        $lockCall =
            '$this->dispatchRepository->findByIdForUpdate((int)$dispatch->id())';

        $revalidationCall =
            '$this->boletaDispatchStatusDomainService->assertCanPoll($lockedDispatch);';

        $pollingResultCall =
            '$lockedDispatch->withPollingResult(';

        $lockPosition = strpos(
            $compactSource,
            $lockCall
        );

        $revalidationPosition = strpos(
            $compactSource,
            $revalidationCall
        );

        $pollingResultPosition = strpos(
            $compactSource,
            $pollingResultCall
        );

        $this->assertIsInt(
            $lockPosition
        );

        $this->assertIsInt(
            $revalidationPosition,
            'La FASE 3 debe revalidar el dispatch recién bloqueado.'
        );

        $this->assertIsInt(
            $pollingResultPosition
        );

        $this->assertGreaterThan(
            $lockPosition,
            $revalidationPosition,
            'La revalidación debe ocurrir después de findByIdForUpdate().'
        );

        $this->assertLessThan(
            $pollingResultPosition,
            $revalidationPosition,
            'La revalidación debe ocurrir antes de withPollingResult().'
        );
    }
    public function test_rsc_del_sii_se_mapea_como_dispatch_rejected(): void
    {
        $reflection = new ReflectionClass(
            PollBoletaDispatchStatusUseCase::class
        );

        $instance = $reflection->newInstanceWithoutConstructor();

        $method = $reflection->getMethod(
            'mapDispatchStatus'
        );

        $method->setAccessible(true);

        $status = $method->invoke(
            $instance,
            'RSC'
        );

        $this->assertSame(
            'rejected',
            $status
        );
    }
}