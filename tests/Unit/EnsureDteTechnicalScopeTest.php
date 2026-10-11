<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureDteTechnicalScope;
use App\Modules\Auth\Domain\Entities\AuthUser;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Entities\FolioReservation;
use App\Modules\Dte\Domain\Entities\SiiDispatch;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioReservationRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiDispatchRepositoryInterface;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Mockery;
use Tests\TestCase;

final class EnsureDteTechnicalScopeTest extends TestCase
{
    private DteDocumentRepositoryInterface $documents;
    private SiiDispatchRepositoryInterface $dispatches;
    private FolioReservationRepositoryInterface $reservations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documents = Mockery::mock(DteDocumentRepositoryInterface::class);
        $this->dispatches = Mockery::mock(SiiDispatchRepositoryInterface::class);
        $this->reservations = Mockery::mock(FolioReservationRepositoryInterface::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_humano_pasa_sin_forzar_external_system_id(): void
    {
        $this->documents->shouldNotReceive('findById');
        $this->dispatches->shouldNotReceive('findById');
        $this->reservations->shouldNotReceive('findByCompanyAndId');

        $request = Request::create('/api/internal/dte/document', 'POST');
        $request->attributes->set('auth_account_type', AuthUser::ACCOUNT_TYPE_HUMAN);
        $request->attributes->set('auth_company_id', 1);
        $request->attributes->set('auth_external_system_id', null);

        $response = $this->middleware()->handle(
            $request,
            static fn (Request $request) => response()->json([
                'external_system_id' => $request->input('external_system_id'),
            ])
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($response->getData(true)['external_system_id']);
    }

    public function test_tecnico_fuerza_external_system_id_del_token_si_cliente_no_lo_envia(): void
    {
        $request = $this->technicalRequest('/api/internal/dte/document', 'POST');

        $response = $this->middleware()->handle(
            $request,
            static fn (Request $request) => response()->json([
                'external_system_id' => $request->input('external_system_id'),
            ])
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(7, $response->getData(true)['external_system_id']);
    }

    public function test_tecnico_rechaza_external_system_id_distinto_en_payload(): void
    {
        $request = $this->technicalRequest(
            '/api/internal/dte/document',
            'POST',
            ['external_system_id' => 99]
        );

        $response = $this->middleware()->handle(
            $request,
            static fn () => response()->json(['ok' => true])
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_documento_de_otro_sistema_no_es_visible_para_tecnico(): void
    {
        $document = $this->document(
            id: 25,
            companyId: 1,
            externalSystemId: 8,
        );

        $this->documents
            ->shouldReceive('findById')
            ->once()
            ->with(25)
            ->andReturn($document);

        $request = $this->technicalRequestWithRouteParameter(
            '/api/internal/dte/documents/25/build-xml',
            'POST',
            'documentId',
            25
        );

        $response = $this->middleware()->handle(
            $request,
            static fn () => response()->json(['ok' => true])
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_dispatch_de_otro_sistema_no_es_visible_para_tecnico(): void
    {
        $dispatch = $this->dispatch(
            id: 44,
            companyId: 1,
            dteDocumentId: 25,
        );

        $document = $this->document(
            id: 25,
            companyId: 1,
            externalSystemId: 8,
        );

        $this->dispatches
            ->shouldReceive('findById')
            ->once()
            ->with(44)
            ->andReturn($dispatch);

        $this->documents
            ->shouldReceive('findById')
            ->once()
            ->with(25)
            ->andReturn($document);

        $request = $this->technicalRequestWithRouteParameter(
            '/api/internal/dte/dispatches/44/poll-upload-status',
            'POST',
            'dispatchId',
            44
        );

        $response = $this->middleware()->handle(
            $request,
            static fn () => response()->json(['ok' => true])
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_reserva_de_otro_sistema_no_es_visible_para_tecnico(): void
    {
        $reservation = $this->reservation(
            id: 12,
            companyId: 1,
            externalSystemId: 8,
        );

        $this->reservations
            ->shouldReceive('findByCompanyAndId')
            ->once()
            ->with(1, 12)
            ->andReturn($reservation);

        $request = $this->technicalRequestWithRouteParameter(
            '/api/internal/dte/folio-reservations/12',
            'GET',
            'reservationId',
            12
        );

        $response = $this->middleware()->handle(
            $request,
            static fn () => response()->json(['ok' => true])
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_recursos_del_mismo_ambito_pasan(): void
    {
        $document = $this->document(
            id: 25,
            companyId: 1,
            externalSystemId: 7,
        );

        $this->documents
            ->shouldReceive('findById')
            ->once()
            ->with(25)
            ->andReturn($document);

        $request = $this->technicalRequestWithRouteParameter(
            '/api/internal/dte/documents/25/build-xml',
            'POST',
            'documentId',
            25
        );

        $response = $this->middleware()->handle(
            $request,
            static fn () => response()->json(['ok' => true])
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    private function middleware(): EnsureDteTechnicalScope
    {
        return new EnsureDteTechnicalScope(
            dteDocumentRepository: $this->documents,
            siiDispatchRepository: $this->dispatches,
            folioReservationRepository: $this->reservations,
        );
    }

    private function technicalRequest(
        string $uri,
        string $method,
        array $parameters = [],
    ): Request {
        $request = Request::create($uri, $method, $parameters);
        $request->attributes->set(
            'auth_account_type',
            AuthUser::ACCOUNT_TYPE_TECHNICAL
        );
        $request->attributes->set('auth_company_id', 1);
        $request->attributes->set('auth_external_system_id', 7);

        return $request;
    }

    private function technicalRequestWithRouteParameter(
        string $uri,
        string $method,
        string $parameter,
        int $value,
    ): Request {
        $request = $this->technicalRequest($uri, $method);

        $route = new Route([$method], $uri, static fn () => null);
        $route->bind($request);
        $route->setParameter($parameter, $value);

        $request->setRouteResolver(static fn () => $route);

        return $request;
    }

    private function document(
        int $id,
        int $companyId,
        int $externalSystemId,
    ): DteDocument {
        return new DteDocument(
            id: $id,
            externalId: 'scope-test-' . $id,
            companyId: $companyId,
            dteType: DteType::FACTURA_ELECTRTONICA,
            issueDate: '2026-10-08',
            status: 'signed',
            receiver: new ReceiverData(
                document: '11111111-1',
                name: 'Receptor Test',
            ),
            netAmount: 1000.0,
            exemptAmount: 0.0,
            taxAmount: 190.0,
            totalAmount: 1190.0,
            items: [],
            externalSystemId: $externalSystemId,
        );
    }

    private function dispatch(
        int $id,
        int $companyId,
        int $dteDocumentId,
    ): SiiDispatch {
        return new SiiDispatch(
            id: $id,
            batchUuid: 'scope-test-dispatch-' . $id,
            companyId: $companyId,
            dteDocumentId: $dteDocumentId,
            environment: 'cert',
            transportType: 'soap_upload_factura',
            status: 'sent',
        );
    }

    private function reservation(
        int $id,
        int $companyId,
        int $externalSystemId,
    ): FolioReservation {
        return new FolioReservation(
            id: $id,
            cafId: 1,
            externalSystemId: $externalSystemId,
            companyId: $companyId,
            siiDocumentTypeCode: '33',
            branchOfficeNumber: null,
            facilityNumber: null,
            externalBranchCode: null,
            folioRangeFrom: 1,
            folioRangeTo: 10,
            currentFolio: 1,
            reservedQuantity: 10,
            reservedAt: '2026-10-08 00:00:00',
            expiresAt: null,
            isCurrentlyValid: true,
            isActive: true,
        );
    }
}
