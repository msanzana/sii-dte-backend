<?php
namespace App\Modules\Dte\Domain\RepositoryContracts;
interface SystemSettingRepositoryInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value, ?string $description = null): void;
}
