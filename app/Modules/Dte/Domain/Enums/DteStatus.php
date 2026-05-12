<?php
namespace App\Modules\Dte\Domain\Enums;
enum DteStatus: string
{
    case DRAFT = 'draft';
    case READY_FOR_XML = 'ready_for_xml';
    case XML_BUILT = 'xml_built';
    case TED_BUILD = 'ted_build';
    case SIGNED = 'signed';
    case QUEUED = 'queued';
    case SENDING = 'sending';
    case SENT = 'sent';
    case ACCEPTED = 'accepted';
    case ACCEPTED_WITH_REPAROS = 'accepted_with_reparos';
    case REJECTED = 'rejected';
    case NEEDS_RESEND = 'needs_resend';
    case FAILED = 'failed';
    case CANCELED = 'canceled';

}
