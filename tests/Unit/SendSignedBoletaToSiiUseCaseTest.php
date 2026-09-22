<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\UseCases\Dispatch\SendSignedBoletaToSiiUseCase;
use ReflectionMethod;
use Tests\TestCase;
use App\Modules\Dte\Infrastructure\Sii\SiiBoletaTokenProviderService;
use ReflectionClass;

final class SendSignedBoletaToSiiUseCaseTest extends TestCase
{
    public function test_el_envio_de_boleta_entrega_el_exponente_rsa_a_la_autenticacion(): void
    {
        $method = new ReflectionMethod(
            SendSignedBoletaToSiiUseCase::class,
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

        $this->assertStringContainsString(
            'exponentBase64:$certificateContext->exponentBase64',
            $compactSource,
            'SendSignedBoletaToSiiUseCase debe enviar exponentBase64 al proveedor de token de boletas.'
        );
    }
    public function test_el_envio_de_boleta_usa_el_proveedor_compartido_de_token(): void
    {
        $reflection = new ReflectionClass(
            SendSignedBoletaToSiiUseCase::class
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
            'SendSignedBoletaToSiiUseCase debe recibir SiiBoletaTokenProviderService.'
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
        $compactSource = preg_replace(
            '/\s+/',
            '',
            $methodSource
        );

        $this->assertIsString(
            $compactSource
        );

        $this->assertStringContainsString(
            '$this->siiBoletaTokenProviderService->get(',
            $compactSource,
            'El envío de boleta debe obtener el token mediante SiiBoletaTokenProviderService.'
        );

        $this->assertStringNotContainsString(
            '$this->siiBoletaApiAuthenticationService->authenticate(',
            $compactSource,
            'El envío de boleta no debe autenticarse directamente contra el SII.'
        );

        $this->assertStringContainsString(
            'companyId:$certificateContext->companyId',
            $compactSource
        );

        $this->assertStringContainsString(
            'certificateId:$certificateContext->certificateId',
            $compactSource
        );
    }
    public function test_el_envio_de_boleta_entrega_los_ruts_requeridos_al_upload_multipart(): void
    {
        $reflection = new ReflectionClass(
            SendSignedBoletaToSiiUseCase::class
        );

        $this->assertTrue(
            $reflection->hasMethod('splitRut'),
            'SendSignedBoletaToSiiUseCase debe poder separar el RUT de la empresa.'
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

        $compactSource = preg_replace(
            '/\s+/',
            '',
            $methodSource
        );

        $this->assertIsString(
            $compactSource
        );

        $this->assertStringContainsString(
            '[$companyRutBody,$companyRutDv]=$this->splitRut($company->rut());',
            $compactSource
        );

        $this->assertStringContainsString(
            "'dte.sii.sender.rut_body'",
            $methodSource
        );

        $this->assertStringContainsString(
            "'dte.sii.sender.rut_dv'",
            $methodSource
        );

        $this->assertStringContainsString(
            'senderRutBody:$senderRutBody',
            $compactSource
        );

        $this->assertStringContainsString(
            'senderRutDv:$senderRutDv',
            $compactSource
        );

        $this->assertStringContainsString(
            'companyRutBody:$companyRutBody',
            $compactSource
        );

        $this->assertStringContainsString(
            'companyRutDv:$companyRutDv',
            $compactSource
        );
    }
    public function test_el_envio_de_boleta_protege_contra_uploads_duplicados(): void
    {
        $reflection = new ReflectionClass(
            SendSignedBoletaToSiiUseCase::class
        );

        $this->assertTrue(
            $reflection->hasMethod('assertNoUnresolvedDispatch'),
            'SendSignedBoletaToSiiUseCase debe implementar la protección contra dispatch no resueltos.'
        );

        $executeMethod = $reflection->getMethod(
            'execute'
        );

        $lines = file(
            $executeMethod->getFileName()
        );

        $this->assertIsArray(
            $lines
        );

        $executeSource = implode(
            '',
            array_slice(
                $lines,
                $executeMethod->getStartLine() - 1,
                $executeMethod->getEndLine() - $executeMethod->getStartLine() + 1
            )
        );

        $compactExecuteSource = preg_replace(
            '/\s+/',
            '',
            $executeSource
        );

        $this->assertIsString(
            $compactExecuteSource
        );

        $protectionCall =
            '$this->assertNoUnresolvedDispatch($document);';

        $tokenCall =
            '$this->siiBoletaTokenProviderService->get(';

        $uploadCall =
            '$this->siiBoletaApiUploadService->upload(';

        $this->assertStringContainsString(
            $protectionCall,
            $compactExecuteSource
        );

        $protectionPosition = strpos(
            $compactExecuteSource,
            $protectionCall
        );

        $tokenPosition = strpos(
            $compactExecuteSource,
            $tokenCall
        );

        $uploadPosition = strpos(
            $compactExecuteSource,
            $uploadCall
        );

        $this->assertIsInt(
            $protectionPosition
        );

        $this->assertIsInt(
            $tokenPosition
        );

        $this->assertIsInt(
            $uploadPosition
        );

        $this->assertLessThan(
            $tokenPosition,
            $protectionPosition,
            'La protección contra duplicados debe ejecutarse antes de obtener el TOKEN.'
        );

        $this->assertLessThan(
            $uploadPosition,
            $protectionPosition,
            'La protección contra duplicados debe ejecutarse antes del POST al SII.'
        );

        $protectionMethod = $reflection->getMethod(
            'assertNoUnresolvedDispatch'
        );

        $protectionSource = implode(
            '',
            array_slice(
                $lines,
                $protectionMethod->getStartLine() - 1,
                $protectionMethod->getEndLine() - $protectionMethod->getStartLine() + 1
            )
        );

        $this->assertStringContainsString(
            'findLatestByDocumentId',
            $protectionSource
        );
    }
    public function test_el_token_y_el_upload_de_boleta_se_ejecutan_fuera_de_la_transaccion_de_preparacion(): void
    {
        $reflection = new ReflectionClass(
            SendSignedBoletaToSiiUseCase::class
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

        $compactSource = preg_replace(
            '/\s+/',
            '',
            $methodSource
        );

        $this->assertIsString(
            $compactSource
        );

        $preparedTransaction =
            '$prepared=DB::transaction(';

        $preparedDocument =
            "\$document=\$prepared['document'];";

        $tokenCall =
            '$this->siiBoletaTokenProviderService->get(';

        $uploadCall =
            '$this->siiBoletaApiUploadService->upload(';

        $this->assertStringContainsString(
            $preparedTransaction,
            $compactSource,
            'La preparación del envío de boleta debe ejecutarse en una transacción separada y devolver un contexto preparado.'
        );

        $this->assertStringContainsString(
            $preparedDocument,
            $compactSource,
            'Después de cerrar la transacción debe recuperarse el documento desde el contexto preparado.'
        );

        $transactionPosition = strpos(
            $compactSource,
            $preparedTransaction
        );

        $preparedDocumentPosition = strpos(
            $compactSource,
            $preparedDocument
        );

        $tokenPosition = strpos(
            $compactSource,
            $tokenCall
        );

        $uploadPosition = strpos(
            $compactSource,
            $uploadCall
        );

        $this->assertIsInt(
            $transactionPosition
        );

        $this->assertIsInt(
            $preparedDocumentPosition
        );

        $this->assertIsInt(
            $tokenPosition
        );

        $this->assertIsInt(
            $uploadPosition
        );

        $this->assertLessThan(
            $tokenPosition,
            $preparedDocumentPosition,
            'El TOKEN debe obtenerse después de cerrar la transacción de preparación.'
        );

        $this->assertLessThan(
            $uploadPosition,
            $preparedDocumentPosition,
            'El upload al SII debe ejecutarse después de cerrar la transacción de preparación.'
        );

        $preparationSource = substr(
            $compactSource,
            $transactionPosition,
            $preparedDocumentPosition - $transactionPosition
        );

        $this->assertStringNotContainsString(
            '$this->siiBoletaTokenProviderService->get(',
            $preparationSource,
            'La transacción de preparación no debe contactar al servicio de autenticación del SII.'
        );

        $this->assertStringNotContainsString(
            '$this->siiBoletaApiUploadService->upload(',
            $preparationSource,
            'La transacción de preparación no debe ejecutar el POST al SII.'
        );
    }
    public function test_el_dispatch_de_boleta_se_crea_pending_antes_de_contactar_al_sii(): void
    {
        $reflection = new ReflectionClass(
            SendSignedBoletaToSiiUseCase::class
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

        $compactSource = preg_replace(
            '/\s+/',
            '',
            $methodSource
        );

        $this->assertIsString(
            $compactSource
        );

        $preparedTransaction =
            '$prepared=DB::transaction(';

        $preparedDocument =
            "\$document=\$prepared['document'];";

        $pendingStatus =
            'status:DispatchStatus::PENDING->value';

        $createDispatch =
            '$this->dispatchRepository->create(';

        $preparedDispatch =
            "'dispatch'=>\$savedDispatch";

        $recoverDispatch =
            "\$dispatch=\$prepared['dispatch'];";

        $tokenCall =
            '$this->siiBoletaTokenProviderService->get(';

        $uploadCall =
            '$this->siiBoletaApiUploadService->upload(';

        $this->assertStringContainsString(
            $pendingStatus,
            $compactSource,
            'El dispatch inicial de boleta debe crearse en estado PENDING.'
        );

        $this->assertStringContainsString(
            $createDispatch,
            $compactSource,
            'El dispatch debe persistirse antes de contactar al SII.'
        );

        $this->assertStringContainsString(
            $preparedDispatch,
            $compactSource,
            'La transacción de preparación debe devolver el dispatch persistido.'
        );

        $this->assertStringContainsString(
            $recoverDispatch,
            $compactSource,
            'Después del commit debe recuperarse el dispatch preparado.'
        );

        $transactionPosition = strpos(
            $compactSource,
            $preparedTransaction
        );

        $preparedDocumentPosition = strpos(
            $compactSource,
            $preparedDocument
        );

        $pendingPosition = strpos(
            $compactSource,
            $pendingStatus
        );

        $createPosition = strpos(
            $compactSource,
            $createDispatch
        );

        $tokenPosition = strpos(
            $compactSource,
            $tokenCall
        );

        $uploadPosition = strpos(
            $compactSource,
            $uploadCall
        );

        $this->assertIsInt(
            $transactionPosition
        );

        $this->assertIsInt(
            $preparedDocumentPosition
        );

        $this->assertIsInt(
            $pendingPosition
        );

        $this->assertIsInt(
            $createPosition
        );

        $this->assertIsInt(
            $tokenPosition
        );

        $this->assertIsInt(
            $uploadPosition
        );

        $this->assertGreaterThan(
            $transactionPosition,
            $pendingPosition,
            'El dispatch PENDING debe crearse dentro de la transacción de preparación.'
        );

        $this->assertLessThan(
            $preparedDocumentPosition,
            $pendingPosition,
            'El dispatch PENDING debe crearse antes de salir de la transacción.'
        );

        $this->assertLessThan(
            $tokenPosition,
            $createPosition,
            'El dispatch debe persistirse antes de obtener el TOKEN.'
        );

        $this->assertLessThan(
            $uploadPosition,
            $createPosition,
            'El dispatch debe persistirse antes del POST al SII.'
        );
    }
    public function test_el_dispatch_de_boleta_pasa_a_sending_antes_del_post_al_sii(): void
    {
        $reflection = new ReflectionClass(
            SendSignedBoletaToSiiUseCase::class
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

        $compactSource = preg_replace(
            '/\s+/',
            '',
            $methodSource
        );

        $this->assertIsString(
            $compactSource
        );

        $sendingStatus =
            'DispatchStatus::SENDING->value';

        $withStatusCall =
            '$dispatch->withStatus(';

        $updateCall =
            '$this->dispatchRepository->update(';

        $uploadCall =
            '$this->siiBoletaApiUploadService->upload(';

        $this->assertStringContainsString(
            $sendingStatus,
            $compactSource,
            'El dispatch de boleta debe pasar a SENDING antes del upload.'
        );

        $this->assertStringContainsString(
            $withStatusCall,
            $compactSource,
            'La transición a SENDING debe realizarse sobre el mismo dispatch.'
        );

        $sendingPosition = strpos(
            $compactSource,
            $sendingStatus
        );

        $updatePosition = strpos(
            $compactSource,
            $updateCall
        );

        $uploadPosition = strpos(
            $compactSource,
            $uploadCall
        );

        $this->assertIsInt(
            $sendingPosition
        );

        $this->assertIsInt(
            $updatePosition
        );

        $this->assertIsInt(
            $uploadPosition
        );

        $this->assertLessThan(
            $uploadPosition,
            $sendingPosition,
            'El estado SENDING debe establecerse antes del POST al SII.'
        );

        $this->assertLessThan(
            $uploadPosition,
            $updatePosition,
            'El dispatch SENDING debe persistirse antes del POST al SII.'
        );
    }
    public function test_el_corte_de_conexion_de_boleta_deja_delivery_unknown_y_documento_sending(): void
    {
        $reflection = new ReflectionClass(
            SendSignedBoletaToSiiUseCase::class
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

        $compactSource = preg_replace(
            '/\s+/',
            '',
            $methodSource
        );

        $this->assertIsString(
            $compactSource
        );

        $fileSource = file_get_contents(
            $method->getFileName()
        );

        $this->assertIsString(
            $fileSource
        );

        $compactFileSource = preg_replace(
            '/\s+/',
            '',
            $fileSource
        );

        $this->assertIsString(
            $compactFileSource
        );

        $uploadCall =
            '$this->siiBoletaApiUploadService->upload(';

        $connectionCatch =
            'catch(ConnectionException$e){';

        $deliveryUnknown =
            'DispatchStatus::DELIVERY_UNKNOWN->value';

        $transactionCall =
            'DB::transaction(';

        $withSendingStatus =
            '->withSendingStatus()';

        $this->assertStringContainsString(
            $connectionCatch,
            $compactSource,
            'El upload de boleta debe capturar ConnectionException porque el SII puede haber recibido el POST.'
        );

        $this->assertStringContainsString(
            'useIlluminate\Http\Client\ConnectionException;',
            $compactFileSource,
            'SendSignedBoletaToSiiUseCase debe importar la ConnectionException de Laravel HTTP.'
        );

        $this->assertStringContainsString(
            $deliveryUnknown,
            $compactSource,
            'Una pérdida de conexión durante el upload debe dejar el dispatch en DELIVERY_UNKNOWN.'
        );

        $this->assertStringContainsString(
            $withSendingStatus,
            $compactSource,
            'El documento debe quedar en SENDING mientras se reconcilia si el SII recibió la boleta.'
        );

        $uploadPosition = strpos(
            $compactSource,
            $uploadCall
        );

        $catchPosition = strpos(
            $compactSource,
            $connectionCatch
        );

        $deliveryUnknownPosition = strpos(
            $compactSource,
            $deliveryUnknown
        );

        $transactionAfterCatchPosition = strpos(
            $compactSource,
            $transactionCall,
            $catchPosition === false
                ? 0
                : $catchPosition
        );

        $sendingDocumentPosition = strpos(
            $compactSource,
            $withSendingStatus,
            $catchPosition === false
                ? 0
                : $catchPosition
        );

        $this->assertIsInt(
            $uploadPosition
        );

        $this->assertIsInt(
            $catchPosition
        );

        $this->assertIsInt(
            $deliveryUnknownPosition
        );

        $this->assertIsInt(
            $transactionAfterCatchPosition
        );

        $this->assertIsInt(
            $sendingDocumentPosition
        );

        $this->assertGreaterThan(
            $uploadPosition,
            $catchPosition,
            'ConnectionException debe capturarse alrededor del POST de upload.'
        );

        $this->assertGreaterThan(
            $catchPosition,
            $deliveryUnknownPosition,
            'DELIVERY_UNKNOWN debe aplicarse dentro del tratamiento del corte de conexión.'
        );

        $this->assertGreaterThan(
            $catchPosition,
            $transactionAfterCatchPosition,
            'La reconciliación local del corte de conexión debe ejecutarse dentro de una nueva transacción.'
        );

        $this->assertGreaterThan(
            $deliveryUnknownPosition,
            $sendingDocumentPosition,
            'Después de marcar DELIVERY_UNKNOWN, el documento debe mantenerse en SENDING.'
        );
    }
    public function test_si_falla_la_obtencion_del_token_el_dispatch_de_boleta_queda_failed_y_la_excepcion_se_propaga(): void
    {
        $reflection = new ReflectionClass(
            SendSignedBoletaToSiiUseCase::class
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

        $compactSource = preg_replace(
            '/\s+/',
            '',
            $methodSource
        );

        $this->assertIsString(
            $compactSource
        );

        $fileSource = file_get_contents(
            $method->getFileName()
        );

        $this->assertIsString(
            $fileSource
        );

        $compactFileSource = preg_replace(
            '/\s+/',
            '',
            $fileSource
        );

        $this->assertIsString(
            $compactFileSource
        );

        $tokenCall =
            '$this->siiBoletaTokenProviderService->get(';

        $throwableCatch =
            'catch(Throwable$e){';

        $failedStatus =
            'DispatchStatus::FAILED->value';

        $withStatus =
            '$dispatch->withStatus(';

        $errorMessage =
            'errorMessage:$e->getMessage()';

        $rethrow =
            'throw$e;';

        $uploadCall =
            '$this->siiBoletaApiUploadService->upload(';

        $this->assertStringContainsString(
            'useThrowable;',
            $compactFileSource,
            'El caso de uso debe importar Throwable para capturar fallos durante la obtención del TOKEN.'
        );

        $this->assertStringContainsString(
            $throwableCatch,
            $compactSource,
            'La obtención del TOKEN debe estar protegida para actualizar el dispatch si falla.'
        );

        $this->assertStringContainsString(
            $failedStatus,
            $compactSource,
            'Si falla la autenticación, el dispatch debe quedar en FAILED.'
        );

        $this->assertStringContainsString(
            $withStatus,
            $compactSource,
            'El estado FAILED debe aplicarse sobre el mismo dispatch previamente creado.'
        );

        $this->assertStringContainsString(
            $rethrow,
            $compactSource,
            'Después de registrar FAILED, la excepción original debe propagarse.'
        );

        $tokenPosition = strpos(
            $compactSource,
            $tokenCall
        );

        $catchPosition = strpos(
            $compactSource,
            $throwableCatch
        );

        $failedPosition = strpos(
            $compactSource,
            $failedStatus
        );

        $rethrowPosition = strpos(
            $compactSource,
            $rethrow
        );

        $uploadPosition = strpos(
            $compactSource,
            $uploadCall
        );

        $this->assertIsInt(
            $tokenPosition
        );

        $this->assertIsInt(
            $catchPosition
        );

        $this->assertIsInt(
            $failedPosition
        );

        $this->assertIsInt(
            $rethrowPosition
        );

        $this->assertIsInt(
            $uploadPosition
        );

        $this->assertLessThan(
            $catchPosition,
            $tokenPosition,
            'La llamada al proveedor de TOKEN debe ocurrir antes del catch.'
        );

        $this->assertLessThan(
            $failedPosition,
            $catchPosition,
            'FAILED debe aplicarse dentro del tratamiento del error de autenticación.'
        );

        $this->assertLessThan(
            $rethrowPosition,
            $failedPosition,
            'El dispatch debe quedar FAILED antes de propagar la excepción.'
        );

        $this->assertLessThan(
            $uploadPosition,
            $rethrowPosition,
            'El POST al SII debe permanecer después del tratamiento de errores de autenticación.'
        );
    }
        public function test_si_el_endpoint_de_boleta_responde_con_error_conocido_el_dispatch_queda_failed(): void
    {
        $reflection = new ReflectionClass(
            SendSignedBoletaToSiiUseCase::class
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

        $compactSource = preg_replace(
            '/\s+/',
            '',
            $methodSource
        );

        $this->assertIsString(
            $compactSource
        );

        $fileSource = file_get_contents(
            $method->getFileName()
        );

        $this->assertIsString(
            $fileSource
        );

        $compactFileSource = preg_replace(
            '/\s+/',
            '',
            $fileSource
        );

        $this->assertIsString(
            $compactFileSource
        );

        $knownErrorCatch =
            'catch(SiiBoletaSendException$e){';

        $failedStatus =
            'DispatchStatus::FAILED->value';

        $errorMessage =
            'errorMessage:$e->getMessage()';

        $responseHttpStatus =
            'responseHttpStatus:$e->httpStatus()';

        $responseBody =
            'responseBody:$e->rawBody()';

        $rethrow =
            'throw$e;';

        $connectionCatch =
            'catch(ConnectionException$e){';

        $this->assertStringContainsString(
            'useApp\Modules\Dte\Domain\Exceptions\SiiBoletaSendException;',
            $compactFileSource,
            'El caso de uso debe importar SiiBoletaSendException.'
        );

        $this->assertStringContainsString(
            $knownErrorCatch,
            $compactSource,
            'Los errores HTTP conocidos del upload deben tratarse separadamente de ConnectionException.'
        );

        $this->assertStringContainsString(
            $failedStatus,
            $compactSource,
            'Un error conocido del endpoint debe dejar el dispatch en FAILED.'
        );

        $this->assertStringContainsString(
            $rethrow,
            $compactSource,
            'Después de registrar FAILED debe propagarse la excepción original.'
        );

        $connectionCatchPosition = strpos(
            $compactSource,
            $connectionCatch
        );

        $knownErrorCatchPosition = strpos(
            $compactSource,
            $knownErrorCatch
        );

        $failedPosition = strpos(
            $compactSource,
            $failedStatus,
            $knownErrorCatchPosition === false
                ? 0
                : $knownErrorCatchPosition
        );

        $rethrowPosition = strpos(
            $compactSource,
            $rethrow,
            $knownErrorCatchPosition === false
                ? 0
                : $knownErrorCatchPosition
        );
        $knownErrorCatchSource =
            is_int($knownErrorCatchPosition)
            && is_int($rethrowPosition)
                ? substr(
                    $compactSource,
                    $knownErrorCatchPosition,
                    ($rethrowPosition + strlen($rethrow))
                        - $knownErrorCatchPosition
                )
                : '';

        $this->assertIsInt(
            $connectionCatchPosition
        );

        $this->assertIsInt(
            $knownErrorCatchPosition
        );
        $this->assertStringContainsString(
            $responseHttpStatus,
            $knownErrorCatchSource,
            'El catch de SiiBoletaSendException debe conservar el HTTP status devuelto por Pangal.'
        );

        $this->assertStringContainsString(
            $responseBody,
            $knownErrorCatchSource,
            'El catch de SiiBoletaSendException debe conservar el body devuelto por Pangal.'
        );
        $this->assertIsInt(
            $failedPosition
        );

        $this->assertIsInt(
            $rethrowPosition
        );
        $this->assertStringContainsString(
            $responseHttpStatus,
            $knownErrorCatchSource,
            'El catch de SiiBoletaSendException debe conservar el HTTP status devuelto por Pangal.'
        );

        $this->assertStringContainsString(
            $responseBody,
            $knownErrorCatchSource,
            'El catch de SiiBoletaSendException debe conservar el body devuelto por Pangal.'
        );
        $this->assertGreaterThan(
            $connectionCatchPosition,
            $knownErrorCatchPosition,
            'SiiBoletaSendException debe tratarse después de ConnectionException.'
        );

        $this->assertGreaterThan(
            $knownErrorCatchPosition,
            $failedPosition,
            'FAILED debe aplicarse dentro del catch de SiiBoletaSendException.'
        );

        $this->assertGreaterThan(
            $failedPosition,
            $rethrowPosition,
            'La excepción debe propagarse después de guardar FAILED.'
        );
    }
    public function test_el_dispatch_de_boleta_registra_cookie_token_en_la_metadata_y_no_header_legacy(): void
    {
        $method = new ReflectionMethod(
            SendSignedBoletaToSiiUseCase::class,
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

        $this->assertStringContainsString(
            "'Cookie'=>'TOKEN=<redacted>'",
            $compactSource,
            'La metadata del dispatch de boleta debe reflejar el uso real de Cookie: TOKEN.'
        );

        $this->assertStringNotContainsString(
            'token_header_name',
            $compactSource,
            'El dispatch de boleta no debe depender de la configuración legacy token_header_name.'
        );
    }
    public function test_el_guard_de_duplicados_reconoce_un_reproceso_rsc_firmado(): void
    {
        $reflection = new ReflectionClass(
            SendSignedBoletaToSiiUseCase::class
        );

        $executeMethod = $reflection->getMethod(
            'execute'
        );

        $lines = file(
            $executeMethod->getFileName()
        );

        $this->assertIsArray(
            $lines
        );

        /*
        * Primero comprobamos que execute entregue el documento completo
        * al guard y no solamente su ID.
        */
        $executeSource = implode(
            '',
            array_slice(
                $lines,
                $executeMethod->getStartLine() - 1,
                $executeMethod->getEndLine() - $executeMethod->getStartLine() + 1
            )
        );

        $compactExecuteSource = preg_replace(
            '/\s+/',
            '',
            $executeSource
        );

        $this->assertIsString(
            $compactExecuteSource
        );

        $this->assertStringContainsString(
            '$this->assertNoUnresolvedDispatch($document);',
            $compactExecuteSource,
            'El guard necesita recibir el DteDocument completo para reconocer un reproceso controlado.'
        );

        /*
        * Ahora inspeccionamos el guard propiamente tal.
        */
        $guardMethod = $reflection->getMethod(
            'assertNoUnresolvedDispatch'
        );

        $guardSource = implode(
            '',
            array_slice(
                $lines,
                $guardMethod->getStartLine() - 1,
                $guardMethod->getEndLine() - $guardMethod->getStartLine() + 1
            )
        );

        /*
        * Debe seguir consultando el último dispatch.
        */
        $this->assertStringContainsString(
            'findLatestByDocumentId',
            $guardSource
        );

        /*
        * El reproceso sólo puede reconocerse si el documento ya volvió
        * a quedar firmado.
        */
        $this->assertStringContainsString(
            'DteStatus::SIGNED->value',
            $guardSource,
            'Sólo un documento reconstruido y firmado puede habilitar el nuevo upload.'
        );

        /*
        * El documento debe conservar la causa original RSC que fue
        * registrada por withNeedsResend().
        */
        $this->assertStringContainsString(
            "\$document->lastErrorCode() === 'RSC'",
            $guardSource,
            'El documento debe demostrar que proviene del reproceso RSC.'
        );

        /*
        * Y el dispatch histórico también debe corresponder al mismo RSC.
        */
        $this->assertStringContainsString(
            "\$latestDispatch->uploadStatusCode() === 'RSC'",
            $guardSource,
            'El dispatch anterior también debe estar identificado como RSC.'
        );

        /*
        * REJECTED debe seguir existiendo dentro de los estados bloqueantes.
        * No queremos eliminar la protección general.
        */
        $this->assertStringContainsString(
            'DispatchStatus::REJECTED->value',
            $guardSource,
            'REJECTED debe continuar siendo bloqueante para casos normales.'
        );
    }
    public function test_el_sobre_de_boleta_se_firma_antes_de_guardarlo_y_enviarlo(): void
    {
        $source = file_get_contents(
            app_path(
                'Modules/Dte/Application/UseCases/Dispatch/SendSignedBoletaToSiiUseCase.php'
            )
        );

        $this->assertIsString($source);

        /*
        * El caso de uso debe depender del firmador compartido
        * de sobres SetDTE.
        */
        $this->assertStringContainsString(
            'use App\Modules\Dte\Infrastructure\Xml\EnvioDteSignatureService;',
            $source
        );

        $this->assertStringContainsString(
            'private readonly EnvioDteSignatureService $envioDteSignatureService',
            $source
        );

        /*
        * Debe firmar el XML generado por
        * EnvioBoletaEnvelopeBuilderService.
        */
        $this->assertStringContainsString(
            '->SignSetDte(',
            $source
        );

        $this->assertMatchesRegularExpression(
            "/envioXml:\s*\\\$payloadBuild\\['payload_xml'\\]/",
            $source
        );

        /*
        * Debe utilizar el mismo material criptográfico
        * de emisión que ya fue cargado para la empresa.
                */
        $this->assertMatchesRegularExpression(
            '/privateKeyPem:\s*\$certificateContext->privateKeyPem/',
            $source
        );

        $this->assertMatchesRegularExpression(
            '/certificateBase64:\s*\$certificateContext->certificateBase64/',
            $source
        );

        $this->assertMatchesRegularExpression(
            '/modulusBase64:\s*\$certificateContext->modulusBase64/',
            $source
        );

        $this->assertMatchesRegularExpression(
            '/exponentBase64:\s*\$certificateContext->exponentBase64/',
            $source
        );

        /*
        * El archivo persistido para el dispatch debe ser
        * el SOBRE YA FIRMADO, no el payload sin firma.
        */
        $this->assertMatchesRegularExpression(
            '/contents:\s*\$signedEnvelopeXml/',
            $source
        );
        
        $this->assertMatchesRegularExpression(
            '/xmlPayload:\s*\$signedEnvelopeXml/',
            $source
        );
        /*
        * La firma debe ocurrir antes de almacenar el XML
        * definitivo que posteriormente se enviará al SII.
        */
        $signPosition = strpos(
            $source,
            '->SignSetDte('
        );

        $storePosition = strpos(
            $source,
            '->storeString('
        );

        $this->assertNotFalse($signPosition);
        $this->assertNotFalse($storePosition);

        $this->assertTrue(
            $signPosition < $storePosition,
            'El SetDTE debe firmarse antes de guardar el XML del dispatch.'
        );
    }
}
