<?php

namespace App\Modules\Dte\Infrastructure\Persistence\Repositories;

use App\Modules\Dte\Domain\RepositoryContracts\SystemSettingRepositoryInterface;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\SystemSettingEloquentModel;

final class EloquentSystemSettingRepository implements SystemSettingRepositoryInterface
{


    public function get(string $key, mixed $default = null): mixed
    {
        $setting = SystemSettingEloquentModel::query()
                   ->where('key',$key)
                   ->first();
        return $setting?->value ?? $default;
    }

    public function set(string $key, mixed $value, ?string $description = null): void
    {
        SystemSettingEloquentModel::query()->UpdateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
            ]
        );
    }
}
