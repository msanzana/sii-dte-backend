<?php

namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Exceptions\CityNotFoundException;
use App\Modules\Dte\Domain\Exceptions\CompanyNotFoundException;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\LocationRepositoryInterface;
use App\Modules\Dte\Domain\ValueObjects\DtePrintableDocument;
use App\Modules\Dte\Domain\ValueObjects\DtePrintableIssuer;
use App\Modules\Dte\Domain\ValueObjects\DtePrintableLineItem;
use App\Modules\Dte\Domain\ValueObjects\DtePrintableReceiver;
use App\Modules\Dte\Domain\ValueObjects\DtePrintableReference;
use App\Modules\Dte\Domain\ValueObjects\DtePrintableTotals;
use App\Modules\Dte\Domain\Entities\Company;
use App\Modules\Dte\Domain\StorageContracts\CompanyLogoStorageInterface;

final class BuildDtePrintableDocumentService
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly LocationRepositoryInterface $locationRepository,
        private readonly ?DteAmountInWordsService $amountInWordsService = null,
        private readonly ?CompanyLogoStorageInterface $companyLogoStorage = null,
    )
    {}

    public function build(
        DteDocument $document
    ): DtePrintableDocument {
        if ($document->id() === null) {
            throw new \DomainException(
                'El documento debe estar persistido antes de generar su representación imprimible.'
            );
        }

        if ($document->folio() === null) {
            throw new \DomainException(
                'El documento debe tener folio antes de generar su representación imprimible.'
            );
        }

        $company =
            $this->companyRepository
                ->findById(
                    $document->companyId()
                );

        if ($company === null) {
            throw CompanyNotFoundException::withId(
                $document->companyId()
            );
        }

        $issuerLocation =
            $this->locationRepository
                ->findSummaryByCityId(
                    $company->cityId()
                );

        if ($issuerLocation === null) {
            throw CityNotFoundException::withId(
                $company->cityId()
            );
        }

        $receiver =
            $document->receiver();

        $receiverLocation = null;

        if ($receiver->cityId() !== null) {
            $receiverLocation =
                $this->locationRepository
                    ->findSummaryByCityId(
                        $receiver->cityId()
                    );

            if ($receiverLocation === null) {
                throw CityNotFoundException::withId(
                    $receiver->cityId()
                );
            }
        }

        $items =
            array_map(
                static fn ($item) =>
                    new DtePrintableLineItem(
                        lineNumber:
                            $item->lineNumber(),
                        itemCodeType:
                            $item->itemCodeType(),
                        itemCode:
                            $item->itemCode(),
                        name:
                            $item->name(),
                        description:
                            $item->description(),
                        quantity:
                            (float) $item->quantity(),
                        unitPrice:
                            $item->unitPrice(),
                        discountPercent:
                            $item->discountPercent(),
                        discountAmount:
                            $item->discountAmount(),
                        taxExempt:
                            $item->taxExempt(),
                        lineAmount:
                            $item->lineAmount(),
                    ),
                $document->items()
            );

        $references =
            array_map(
                static fn ($reference) =>
                    new DtePrintableReference(
                        lineNumber:
                            $reference->lineNumber(),
                        referencedDteType:
                            $reference->referencedDteType(),
                        referencedFolio:
                            $reference->referencedFolio(),
                        referencedIssueDate:
                            $reference->referencedIssueDate(),
                        referenceCode:
                            $reference->referenceCode(),
                        reason:
                            $reference->reason(),
                    ),
                $document->references()
            );
        $amountInWordsService =
            $this->amountInWordsService
            ?? new DteAmountInWordsService();

        $totalInWords =
            $amountInWordsService->toPesos(
                $document->totalAmount()
            );
        $issuerLogoDataUri =
            $this->buildCompanyLogoDataUri(
                $company
            );
        return new DtePrintableDocument(
            documentId:
                $document->id(),
            externalId:
                $document->externalId(),
            dteType:
                $document->dteType()->value,
            folio:
                $document->folio(),
            issueDate:
                $document->issueDate(),

            issuer:
                new DtePrintableIssuer(
                    rut:
                        $company->rut(),
                    legalName:
                        $company->legalName(),
                    tradeName:
                        $company->tradeName(),
                    giro:
                        $company->giro(),
                    activityCode:
                        $company->siiActivityCode(),
                    address:
                        $company->address(),
                    city:
                        $issuerLocation->cityName(),
                    comune:
                        $issuerLocation->comuneName(),
                    email:
                        $company->dteEmail(),
                    resolutionNumber:
                        $company->resolutionNumber(),
                    resolutionDate:
                        $company->resolutionDate(),
                    siiOfficeName:
                        $company->siiOfficeName(),
                    logoDataUri:
                        $issuerLogoDataUri,
                ),

            receiver:
                new DtePrintableReceiver(
                    document:
                        $receiver->document(),
                    name:
                        $receiver->name(),
                    giro:
                        $receiver->giro(),
                    address:
                        $receiver->address(),
                    city:
                        $receiverLocation?->cityName(),
                    comune:
                        $receiverLocation?->comuneName(),
                    email:
                        $receiver->email(),
                ),

            totals:
                new DtePrintableTotals(
                    net:
                        $document->netAmount(),
                    exempt:
                        $document->exemptAmount(),
                    tax:
                        $document->taxAmount(),
                    total:
                        $document->totalAmount(),
                    totalInWords:
                        $totalInWords,
                ),

            items:
                $items,

            references:
                $references,
        );
    }
    private function buildCompanyLogoDataUri(
        Company $company
    ): ?string {
        if (
            !$company->hasLogo()
            || $this->companyLogoStorage === null
        ) {
            return null;
        }

        $path =
            $company->logoPath();

        $mimeType =
            $company->logoMimeType();

        $expectedSha256 =
            $company->logoSha256();

        if (
            $path === null
            || $mimeType === null
            || $expectedSha256 === null
        ) {
            return null;
        }

        if (
            !in_array(
                $mimeType,
                [
                    'image/png',
                    'image/jpeg',
                ],
                true
            )
        ) {
            return null;
        }

        $contents =
            $this->companyLogoStorage
                ->getContents(
                    $path
                );

        /*
        * Un logo eliminado o ausente no debe impedir imprimir
        * fiscalmente el documento.
        */
        if (
            $contents === null
            || $contents === ''
        ) {
            return null;
        }

        /*
        * La metadata ya contiene el hash del archivo validado.
        * Evitamos imprimir un archivo que haya sido alterado
        * físicamente después de su carga.
        */
        if (
            !hash_equals(
                $expectedSha256,
                hash(
                    'sha256',
                    $contents
                )
            )
        ) {
            return null;
        }

        return
            'data:'
            . $mimeType
            . ';base64,'
            . base64_encode(
                $contents
            );
    }
}