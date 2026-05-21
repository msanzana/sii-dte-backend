<?php
namespace App\Modules\Dte\Domain\Enums;
enum DteStatus: string
{
    case DRAFT = 'draft';
    case READY_FOR_XML = 'ready_for_xml';
    case FOLIO_ASSIGNED = 'folio_assigned';
    case XML_BUILT = 'xml_built';
    case TED_BUILT = 'ted_built';
    case SIGNED = 'signed';
    case QUEUED = 'queued';
    case SENDING = 'sending';
    case SENT = 'sent';
    case ACCEPTED = 'accepted';
    case ACCEPTED_WITH_REPAROS = 'accepted_with_reparos';
    case REJECTED = 'rejected';
    case NEEDS_RESEND = 'needs_resend';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
