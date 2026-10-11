<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureDteTechnicalScope;
use App\Modules\Auth\Domain\Entities\AuthUser;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Entities\FolioReservation;
use App\Modules\Dte\Domain\Entities\SiiDispatch;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioReservationRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiDispatchRepositoryInterface;
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
        $document = Mockery::mock(DteDocument::class);
        $document->shouldReceive('companyId')->once()->andReturn(1);
        $document->shouldReceive('externalSystemId')->once()->andReturn(8);

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
        $dispatch = Mockery::mock(SiiDispatch::class);
        $dispatch->shouldReceive('companyId')->once()->andReturn(1);
        $dispatch->shouldReceive('dteDocumentId')->twice()->andReturn(25);

        $document = Mockery::mock(DteDocument::class);
        $document->shouldReceive('companyId')->once()->andReturn(1);
        $document->shouldReceive('externalSystemId')->once()->andReturn(8);

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
        $reservation = Mockery::mock(FolioReservation::class);
        $reservation->shouldReceive('externalSystemId')->once()->andReturn(8);

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
        $document = Mockery::mock(DteDocument::class);
        $document->shouldReceive('companyId')->once()->andReturn(1);
        $document->shouldReceive('externalSystemId')->once()->andReturn(7);

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
}
