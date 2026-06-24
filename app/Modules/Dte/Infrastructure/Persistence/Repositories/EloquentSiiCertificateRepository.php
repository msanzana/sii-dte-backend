<?php

namespace App\Modules\Dte\Infrastructure\Persistence\Repositories;

use App\Modules\Dte\Domain\Entities\SiiCertificate;
use App\Modules\Dte\Domain\RepositoryContracts\SiiCertificateRepositoryInterface;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\SiiCertificateEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\Mappers\SiiCertificatePersistenceMapper;

final class EloquentSiiCertificateRepository implements SiiCertificateRepositoryInterface
{
    // public function __construct(
    //     private readonly SiiCertificatePersistenceMapper $mapper,
    // ) {}
    public function create(SiiCertificate $certificate): SiiCertificate
    {
        $model = new SiiCertificateEloquentModel();

        $model->fill([
            'company_id' => $certificate->companyId(),
            'alias' => $certificate->alias(),
            'pfx_path' => $certificate->pfxPath(),
            'pfx_password_encrypted' => $certificate->pfxPasswordEncrypted(),
            'serial_number' => $certificate->serialNumber(),
            'subject_name' => $certificate->subjectName(),
            'issuer_name' => $certificate->issuerName(),
            'valid_from' => $certificate->validFrom(),
            'valid_to' => $certificate->validTo(),
            'pfx_sha256' => $certificate->pfxSha256(),
            'metadata_hash' => $certificate->metadataHash(),
            'certificate_fingerprint_sha1' => $certificate->certificateFingerprintSha1(),
            'has_private_key' => $certificate->hasPrivateKey(),
            'is_default' => $certificate->isDefault(),
            'is_active' => $certificate->isActive(),
            'last_validity_check_at' => $certificate->lastValidityCheckAt(),
            'last_validity_status' => $certificate->lastValidityStatus(),
        ]);

        $model->save();

        return $this->toDomain($model);
    }
    public function findById(int $id): ?SiiCertificate
    {
        $model = SiiCertificateEloquentModel::query()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findByCompanyId(int $companyId): array
    {
        return SiiCertificateEloquentModel::query()
            ->where('company_id', $companyId)
            ->orderByDesc('is_default')
            ->orderByDesc('valid_to')
            ->orderByDesc('id')
            ->get()
            ->map(fn(SiiCertificateEloquentModel $model) => $this->toDomain($model))
            ->all();
    }
    
    public function hasDefaultForCompany(int $companyId): bool
    {
        return SiiCertificateEloquentModel::query()
            ->where('company_id', $companyId)
            ->where('is_default', true)
            ->exists();
    }
    public function findDefaultByCompanyId(int $companyId): ?SiiCertificate
    {
        $model = SiiCertificateEloquentModel::query()
            ->where('company_id', $companyId)
            ->where('is_default', true)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }
    public function findDefaultValidByCompanyId(int $companyId): ?SiiCertificate
    {
        $now = now();

        $model = SiiCertificateEloquentModel::query()
            ->where('company_id', $companyId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->whereNotNull('valid_from')
            ->whereNotNull('valid_to')
            ->where('valid_from', '<=', $now)
            ->where('valid_to', '>=', $now)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }
    public function clearDefaultByCompanyId(int $companyId): void
    {
        SiiCertificateEloquentModel::query()
            ->where('company_id', $companyId)
            ->update([
                'is_default' => false,
                'updated_at' => now(),
            ]);
    }
    public function setDefaultById(int $certificateId, bool $isDefault): void
    {
        SiiCertificateEloquentModel::query()
            ->where('id', $certificateId)
            ->update([
                'is_default' => $isDefault,
                'updated_at' => now(),
            ]);
    }

    public function existsDuplicateByCompanyAndHashes(
        int $companyId,
        ?string $pfxSha256,
        ?string $metadataHash
    ): bool {
        if ($pfxSha256 === null && $metadataHash === null) {
            return false;
        }
        return SiiCertificateEloquentModel::query()
            ->where('company_id', $companyId)
            ->where(function ($query) use ($pfxSha256, $metadataHash) {
                if ($pfxSha256 !== null) {
                    $query->orWhere('pfx_sha256', $pfxSha256);
                }
                if ($metadataHash !== null) {
                    $query->orWhere('metadata_hash', $metadataHash);
                }
            })
            ->exists();
    }

    public function updateValiditySnapshot(
        int $certificateId,
        string $lastValidityStatus,
        ?string $lastValidityCheckAt
    ): void {
        SiiCertificateEloquentModel::query()
            ->where('id', $certificateId)
            ->update([
                'last_validity_status' => $lastValidityStatus,
                'last_validity_check_at' => $lastValidityCheckAt,
                'updated_at' => now(),
            ]);
    }

    private function toDomain(SiiCertificateEloquentModel $model): SiiCertificate
    {
        return new SiiCertificate(
            id:$model->id !== null ? (int) $model->id : null,
            companyId: (int) $model->company_id,
            alias: (string) $model->alias,
            pfxPath: (string) $model->pfx_path,
            pfxPasswordEncrypted: (string) $model->pfx_password_encrypted,
            serialNumber: (string) $model->serial_number,
            subjectName: (string) $model->subject_name,
            issuerName: $model->issuer_name,
            validFrom:  $model->valid_from?->format('Y-m-d H:i:s'),
            validTo: $model->valid_to?->format('Y-m-d H:i:s'),
            pfxSha256: $model->pfx_sha256,
            metadataHash: $model->metadata_hash,
            certificateFingerprintSha1: $model->certificate_fingerprint_sha1,
            hasPrivateKey: (bool) $model->has_private_key,
            isDefault: (bool) $model->is_default,
            isActive: (bool) $model->is_active,
            lastValidityCheckAt: $model->last_validity_check_at?->format('Y-m-d H:i:s'),
            lastValidityStatus: $model->last_validity_status,
        );
    }

}
