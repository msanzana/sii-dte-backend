<?php
namespace App\Modules\Dte\Domain\Enums;

enum DteEnvironment:string
{
    case CERT = 'cert';
    case PROD = 'prod';
}