<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Entorno SII por defecto
    |--------------------------------------------------------------------------
    |
    | "cert" para certificación / pruebas.
    | "prod" para producción.
    |
    */
    'default_environment' => env('SII_ENV', 'cert'),

    /*
    |--------------------------------------------------------------------------
    | Rutas privadas del módulo DTE
    |--------------------------------------------------------------------------
    |
    | Aquí guardaremos CAF, certificados, XML generados, XML firmados
    | y respuestas crudas de integraciones externas.
    |
    */
    'storage' => [
        'base' => storage_path('app/private/dte'),
        'caf' => storage_path('app/private/dte/caf'),
        'certificates' => storage_path('app/private/dte/certificates'),
        'xml' => storage_path('app/private/dte/xml'),
        'signed' => storage_path('app/private/dte/signed'),
        'responses' => storage_path('app/private/dte/responses'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Claves de estado del servicio
    |--------------------------------------------------------------------------
    |
    | Estas claves se guardarán en la tabla system_settings para permitir
    | pausar y reanudar la operación del backend.
    |
    */
    'service_state' => [
        'paused_key' => 'dte.service.paused',
        'pause_reason_key' => 'dte.service.pause_reason',
    ],

    /*
    |--------------------------------------------------------------------------
    | Límites operacionales internos
    |--------------------------------------------------------------------------
    |
    | max_detail_lines:
    | Máximo permitido por política interna para líneas de detalle.
    |
    | max_boletas_per_batch:
    | Quedará listo para el módulo de envío masivo de boletas.
    |
    */
    'limits' => [
        'max_detail_lines' => 60,
        'max_boletas_per_batch' => 500,
    ],
];
