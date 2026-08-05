<?php

use App\Jobs\Dte\Aothomation\PumpPendingDispatchPollingJob;
use App\Jobs\Dte\Aothomation\PumpPendingDocumentsJob;
use App\Jobs\Dte\Aothomation\PumpPendingDocumentStatusQueriesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\Dte\Aothomation\ReconcileCafCountersJob;
use App\Jobs\Dte\Aothomation\SyncExpiredFolioReservationsJob;

Artisan::command('inspire', function () {
    $this->comment('DTE automation scheduler ready.');
})->purpose('Display an automation-ready message');

Schedule::job(
    new PumpPendingDocumentsJob(),
    (string) config('dte.automation.queues.pipeline'),
    (string) config('dte.automation.queue_connection')
)
    ->name('dte-automation-pump')
    ->everyTenSeconds()
    ->withoutOverlapping(1)
    ->onOneServer();

Schedule::job(
    new PumpPendingDispatchPollingJob(),
    (string) config('dte.automation.queues.dispatch_polling'),
    (string) config('dte.automation.queue_connection')
)
    ->name('dte-dispatch-polling-pump')
    ->everyTenSeconds()
    ->withoutOverlapping(1)
    ->onOneServer();

Schedule::job(
    new PumpPendingDocumentStatusQueriesJob(),
    (string) config('dte.automation.queues.document_status'),
    (string) config('dte.automation.queue_connection')
)
    ->name('dte-document-status-pump')
    ->everyTenSeconds()
    ->withoutOverlapping(1)
    ->onOneServer();
Schedule::command('dte:refresh-company-certificate-defaults')->hourly();
Schedule::job(
    new SyncExpiredFolioReservationsJob(),
    (string) config(
        'dte.automation.queues.maintenance',
        'dte-maintenance'
    ),
    (string) config(
        'dte.automation.queue_connection'
    )
)
    ->name('dte-expired-folio-reservations')
    ->everyFifteenMinutes()
    ->withoutOverlapping(15)
    ->onOneServer();

Schedule::job(
    new ReconcileCafCountersJob(),
    (string) config(
        'dte.automation.queues.maintenance',
        'dte-maintenance'
    ),
    (string) config(
        'dte.automation.queue_connection'
    )
)
    ->name('dte-caf-counter-reconciliation')
    ->dailyAt('02:30')
    ->withoutOverlapping(60)
    ->onOneServer();