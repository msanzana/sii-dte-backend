<?php

namespace App\Modules\Dte\Infrastructure\Persistence\Repositories;

use App\Modules\Dte\Domain\RepositoryContracts\CompanyCertificateNoticeRepositoryInterface;
use App\Modules\Dte\Domain\Entities\CompanyCertificateNotice;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\CompanyCertificateNoticeEloquentModel;

final class EloquentCompanyCertificateNoticeRepository implements CompanyCertificateNoticeRepositoryInterface
{


    public function findByCompanyId(int $companyId, int $limit = 100): array
    {
       return CompanyCertificateNoticeEloquentModel::query()
                ->where('company_id', $companyId)
                ->orderByDesc('emitted_at')
                ->limit($limit)
                ->get()
                ->map(fn (CompanyCertificateNoticeEloquentModel $model) => $this->toDomain($model))
                ->all();

    }

    public function findById(int $noticeId): ?CompanyCertificateNotice
    {
        $model = CompanyCertificateNoticeEloquentModel::query()->find($noticeId);

        return $model ? $this->toDomain($model) : null;
    }

    public function create(
        int $companyId,
        ?int $userId,
        ?int  $certificateId,
        string $source,
        string $type,
        ?string $code,
        string $title,
        string $message
    ): CompanyCertificateNotice {
        $model = new CompanyCertificateNoticeEloquentModel();
        $model->fill([
            'company_id' => $companyId,
            'user_id' => $userId,
            'certificate_id' => $certificateId,
            'source' => $source,
            'type' => $type,
            'code' => $code,
            'title' => $title,
            'message' => $message,
            'notice_date' => now()->toDateString(),
            'notice_time' => now()->format('H:i:s'),
            'emitted_at' => now(),
            'is_read' => false,
            'is_active' => true,
       ]);
       $model->save();

       return $this->toDomain($model);
    }

    public function updateManualNotice(
        int $noticeId,
        string $type,
        string $title,
        string $message,
        bool $isActive

    ): CompanyCertificateNotice {
        $model = CompanyCertificateNoticeEloquentModel::query()->findOrFail($noticeId);

        $model->fill([
            'type' => $$type,
            'title' => $title,
            'message' => $message,
            'is_active' => $isActive,
        ]);

        $model->save();

        return $this->toDomain($model);
    }

    public function markAsRead(int $noticeId): void
    {
        CompanyCertificateNoticeEloquentModel::query()
            ->where('id', $noticeId)
            ->update([
                'is_read' => true,
                'updated_at' => now(),
        ]);
    }

    public function delete(int $noticeId): void
    {
        CompanyCertificateNoticeEloquentModel::query()
            ->where('id',$noticeId)
            ->delete();
    }

    public function toDomain(CompanyCertificateNoticeEloquentModel $model): CompanyCertificateNotice
    {
        return new CompanyCertificateNotice(
            id: (int) $model->id,
            companyId: (int) $model->company_id,
            userId: $model->user_id !== null ? (int) $model->user_id : null,
            certificateId: $model->certificate_id !== null ? (int) $model->certificate_id : null,
            source: (string) $model->source,
            type: (string) $model->type,
            code: $model->code,
            title: (string) $model->title,
            message: (string) $model->message,
            noticeDate: (string) $model->notice_date,
            noticeTime: (string) $model->notice_time,
            emittedAt: $model->emitted_at?->format('Y-m-d H:i:s') ?? '',
            isRead: (bool) $model->is_read,
            isActive: (bool) $model->is_active,
        );
    }
}
