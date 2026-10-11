<?php

namespace Tests\Unit;

use App\Modules\Auth\Infrastructure\Persistence\Repositories\EloquentAuthRefreshTokenRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class EloquentAuthRefreshTokenRepositoryTechnicalScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('auth_refresh_tokens');

        Schema::create('auth_refresh_tokens', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('external_system_id')->nullable();
            $table->string('token_hash');
            $table->string('user_agent', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->dateTime('expires_at');
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_create_persiste_external_system_id_para_refresh_tecnico(): void
    {
        $repo = new EloquentAuthRefreshTokenRepository();

        $token = $repo->create(
            userId: 10,
            companyId: 1,
            tokenHash: str_repeat('a', 64),
            expiresAt: now()->addHour()->format('Y-m-d H:i:s'),
            userAgent: 'PHPUnit',
            ipAddress: '127.0.0.1',
            externalSystemId: 7,
        );

        $this->assertSame(7, $token->externalSystemId());

        $this->assertDatabaseHas('auth_refresh_tokens', [
            'id' => $token->id(),
            'user_id' => 10,
            'company_id' => 1,
            'external_system_id' => 7,
        ]);

        $reloaded = $repo->findById($token->id());

        $this->assertNotNull($reloaded);
        $this->assertSame(7, $reloaded->externalSystemId());
    }

    public function test_create_humano_mantiene_external_system_id_null(): void
    {
        $repo = new EloquentAuthRefreshTokenRepository();

        $token = $repo->create(
            userId: 11,
            companyId: 2,
            tokenHash: str_repeat('b', 64),
            expiresAt: now()->addHour()->format('Y-m-d H:i:s'),
        );

        $this->assertNull($token->externalSystemId());

        $this->assertNull(
            DB::table('auth_refresh_tokens')
                ->where('id', $token->id())
                ->value('external_system_id')
        );
    }
}
