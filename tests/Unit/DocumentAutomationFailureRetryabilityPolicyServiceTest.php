<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\Services\DocumentAutomationFailureRetryabilityPolicyService;
use App\Modules\Dte\Domain\Exceptions\CompanyNotFoundException;
use App\Modules\Dte\Domain\Exceptions\InvalidDocumentStateException;
use App\Modules\Dte\Domain\Exceptions\InvalidDteException;
use App\Modules\Dte\Domain\Exceptions\InvalidTedDataException;
use RuntimeException;
use Tests\TestCase;

final class DocumentAutomationFailureRetryabilityPolicyServiceTest
    extends TestCase
{
    public function test_runtime_exception_generica_se_considera_retryable(): void
    {
        $policy =
            new DocumentAutomationFailureRetryabilityPolicyService();

        $this->assertTrue(
            $policy->shouldRetry(
                new RuntimeException(
                    'Fallo transitorio de infraestructura.'
                )
            )
        );
    }

    public function test_company_not_found_no_se_considera_retryable(): void
    {
        $policy =
            new DocumentAutomationFailureRetryabilityPolicyService();

        $this->assertFalse(
            $policy->shouldRetry(
                CompanyNotFoundException::withId(
                    1
                )
            )
        );
    }

    public function test_invalid_document_state_no_se_considera_retryable(): void
    {
        $policy =
            new DocumentAutomationFailureRetryabilityPolicyService();

        $this->assertFalse(
            $policy->shouldRetry(
                InvalidDocumentStateException::because(
                    'Estado inválido para continuar.'
                )
            )
        );
    }

    public function test_invalid_dte_no_se_considera_retryable(): void
    {
        $policy =
            new DocumentAutomationFailureRetryabilityPolicyService();

        $this->assertFalse(
            $policy->shouldRetry(
                InvalidDteException::because(
                    'El DTE contiene datos inválidos.'
                )
            )
        );
    }

    public function test_invalid_ted_data_no_se_considera_retryable(): void
    {
        $policy =
            new DocumentAutomationFailureRetryabilityPolicyService();

        $this->assertFalse(
            $policy->shouldRetry(
                InvalidTedDataException::because(
                    'Los datos del TED son inválidos.'
                )
            )
        );
    }
}