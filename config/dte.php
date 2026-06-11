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
    'sii' => [
        'receiver_rut' => env('DTE_SII_RECEIVER_RUT', '60803000-K'),

        'sender' => [
            'rut_body' => env('DTE_SII_SENDER_RUT_BODY', ''),
            'rut_dv' => env('DTE_SII_SENDER_RUT_DV', ''),
        ],
        'cert' => [
            'soap' => [
                'seed_url' => env('DTE_SII_CERT_SEED_URL', 'https://palena.sii.cl/DTEWS/CrSeed.jws'),
                'token_url' => env('DTE_SII_CERT_TOKEN_URL', 'https://palena.sii.cl/DTEWS/GetTokenFromSeed.jws'),
                'query_est_up_url' => env('DTE_SII_CERT_QUERY_EST_UP_URL', 'https://maullin.sii.cl/DTEWS/QueryEstUp.jws'),
                'query_est_dte_url' => env('DTE_SII_CERT_QUERY_EST_DTE_URL', 'https://maullin.sii.cl/DTEWS/QueryEstDte.jws'),
            ],
            'upload' => [
                'url' => env('DTE_SII_CERT_UPLOAD_URL', 'https://maullin.sii.cl/cgi_dte/UPL/DTEUpload'),
                'referer' => env('DTE_SII_CERT_UPLOAD_REFERER', 'http://localhost'),
            ],
        ],

        'prod' => [
            'soap' => [
                'seed_url' => env('DTE_SII_PROD_SEED_URL', ''),
                'token_url' => env('DTE_SII_PROD_TOKEN_URL', ''),
                'query_est_up_url' => env('DTE_SII_PROD_QUERY_EST_UP_URL', ''),
                'query_est_dte_url' => env('DTE_SII_PROD_QUERY_EST_DTE_URL', ''),
            ],
            'upload' => [
                'url' => env('DTE_SII_PROD_UPLOAD_URL', ''),
                'referer' => env('DTE_SII_PROD_UPLOAD_REFERER', 'http://localhost'),
            ],
        ],
        'boleta' => [
            'docs_url' => env('DTE_SII_BOLETA_DOCS_URL', 'https://www4c.sii.cl/bolcoreinternetui/api/'),
            'wrap_in_envio' => env('DTE_SII_BOLETA_WRAP_IN_ENVIO', true),

            'cert' => [
                'seed_url' => env('DTE_SII_BOLETA_CERT_SEED_URL', ''),
                'token_url' => env('DTE_SII_BOLETA_CERT_TOKEN_URL', ''),
                'send_url' => env('DTE_SII_BOLETA_CERT_SEND_URL', ''),
                'send_status_url' => env('DTE_SII_BOLETA_CERT_SEND_STATUS_URL', ''),
                'document_status_url' => env('DTE_SII_BOLETA_CERT_DOCUMENT_STATUS_URL', ''),
                'token_header_name' => env('DTE_SII_BOLETA_CERT_TOKEN_HEADER_NAME', 'Authorization'),
                'token_header_prefix' => env('DTE_SII_BOLETA_CERT_TOKEN_HEADER_PREFIX', 'Bearer '),
                'send_http_method' => env('DTE_SII_BOLETA_CERT_SEND_HTTP_METHOD', 'POST'),
                'send_mode' => env('DTE_SII_BOLETA_CERT_SEND_MODE', 'raw_xml'),
                'send_content_type' => env('DTE_SII_BOLETA_CERT_SEND_CONTENT_TYPE', 'application/xml; charset=UTF-8'),
                'send_body_field' => env('DTE_SII_BOLETA_CERT_SEND_BODY_FIELD', 'xml'),
                'status_http_method' => env('DTE_SII_BOLETA_CERT_STATUS_HTTP_METHOD', 'POST'),
                'status_track_id_field' => env('DTE_SII_BOLETA_CERT_STATUS_TRACK_ID_FIELD', 'track_id'),
            ],

            'prod' => [
                'seed_url' => env('DTE_SII_BOLETA_PROD_SEED_URL', ''),
                'token_url' => env('DTE_SII_BOLETA_PROD_TOKEN_URL', ''),
                'send_url' => env('DTE_SII_BOLETA_PROD_SEND_URL', ''),
                'send_status_url' => env('DTE_SII_BOLETA_PROD_SEND_STATUS_URL', ''),
                'document_status_url' => env('DTE_SII_BOLETA_PROD_DOCUMENT_STATUS_URL', ''),
                'token_header_name' => env('DTE_SII_BOLETA_PROD_TOKEN_HEADER_NAME', 'Authorization'),
                'token_header_prefix' => env('DTE_SII_BOLETA_PROD_TOKEN_HEADER_PREFIX', 'Bearer '),
                'send_http_method' => env('DTE_SII_BOLETA_PROD_SEND_HTTP_METHOD', 'POST'),
                'send_mode' => env('DTE_SII_BOLETA_PROD_SEND_MODE', 'raw_xml'),
                'send_content_type' => env('DTE_SII_BOLETA_PROD_SEND_CONTENT_TYPE', 'application/xml; charset=UTF-8'),
                'send_body_field' => env('DTE_SII_BOLETA_PROD_SEND_BODY_FIELD', 'xml'),
                'status_http_method' => env('DTE_SII_BOLETA_PROD_STATUS_HTTP_METHOD', 'POST'),
                'status_track_id_field' => env('DTE_SII_BOLETA_PROD_STATUS_TRACK_ID_FIELD', 'track_id'),
            ],
        ],
    ],

    'automation' => [
        'enabled' => env('DTE_AUTOMATION_ENABLED', true),

        'queue_connection' => env('DTE_AUTOMATION_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),

        'queues' => [
            'pipeline' => env('DTE_AUTOMATION_PIPELINE_QUEUE', 'dte-pipeline'),
            'dispatch_polling' => env('DTE_AUTOMATION_DISPATCH_POLLING_QUEUE', 'dte-dispatch-polling'),
            'document_status' => env('DTE_AUTOMATION_DOCUMENT_STATUS_QUEUE', 'dte-document-status'),
        ],

        'limits' => [
            'documents_per_pump' => env('DTE_AUTOMATION_DOCUMENTS_PER_PUMP', 50),
            'dispatches_per_pump' => env('DTE_AUTOMATION_DISPATCHES_PER_PUMP', 50),
            'document_status_queries_per_pump' => env('DTE_AUTOMATION_DOCUMENT_STATUS_QUERIES_PER_PUMP', 50),
        ],

        'delays' => [
            'immediate_requeue_seconds' => env('DTE_AUTOMATION_IMMEDIATE_REQUEUE_SECONDS', 1),
            'dispatch_poll_seconds' => env('DTE_AUTOMATION_DISPATCH_POLL_SECONDS', 30),
            'document_status_query_seconds' => env('DTE_AUTOMATION_DOCUMENT_STATUS_QUERY_SECONDS', 60),
        ],

        'tries' => [
            'pipeline' => env('DTE_AUTOMATION_PIPELINE_TRIES', 5),
            'dispatch_polling' => env('DTE_AUTOMATION_DISPATCH_POLLING_TRIES', 5),
            'document_status' => env('DTE_AUTOMATION_DOCUMENT_STATUS_TRIES', 5),
        ],

        'backoff' => [
            'pipeline' => [5, 15, 60],
            'dispatch_polling' => [10, 30, 120],
            'document_status' => [10, 60, 180],
        ],
    ],
];
