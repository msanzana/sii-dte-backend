<?php
namespace App\Modules\Dte\Domain\Enums;
enum DispatchStatus: string
{
    case PENDING = 'pending';
    case SENDING = 'sending';
    case UPLOAD_OK = 'upload_ok';
    case UPLOAD_REJECTED = 'upload_rejected';
    case POLLING = 'polling';
    case PROCESSED = 'processed';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case FAILED = 'failed';
}
