<?php

namespace App\Modules\Dte\Infrastructure\Pdf\Components;

use App\Modules\Dte\Domain\Enums\PrintCopyType;
use App\Modules\Dte\Domain\ValueObjects\DtePrintableCopy;

final class DteFiscalPrintHtmlBuilder
{
    public function renderTotalInWords(
        ?string $totalInWords
    ): string {
        if (
            $totalInWords === null
            || trim($totalInWords) === ''
        ) {
            return '';
        }

        return
            '<div class="fiscal-total-in-words">'
            . '<strong>SON:</strong> '
            . $this->escape(
                trim($totalInWords)
            )
            . '</div>';
    }

    public function renderAcknowledgement(
        DtePrintableCopy $copy
    ): string {
        if (
            $copy->type()
            !== PrintCopyType::CEDIBLE
        ) {
            return '';
        }

        return
            '<section class="fiscal-acknowledgement">'

            . '<div class="fiscal-ack-title">'
            . 'ACUSE DE RECIBO'
            . '</div>'

            . '<table class="fiscal-ack-fields">'

            . '<tr>'
            . '<td colspan="2">'
            . '<strong>Nombre:</strong>'
            . '<span class="fiscal-ack-fill">&nbsp;</span>'
            . '</td>'
            . '</tr>'

            . '<tr>'
            . '<td class="fiscal-ack-half">'
            . '<strong>RUT:</strong>'
            . '<span class="fiscal-ack-fill">&nbsp;</span>'
            . '</td>'

            . '<td class="fiscal-ack-half">'
            . '<strong>Fecha:</strong>'
            . '<span class="fiscal-ack-fill">&nbsp;</span>'
            . '</td>'
            . '</tr>'

            . '<tr>'
            . '<td colspan="2">'
            . '<strong>Recinto:</strong>'
            . '<span class="fiscal-ack-fill">&nbsp;</span>'
            . '</td>'
            . '</tr>'

            . '<tr>'
            . '<td colspan="2" class="fiscal-ack-signature">'
            . '<strong>Firma:</strong>'
            . '<span class="fiscal-ack-fill">&nbsp;</span>'
            . '</td>'
            . '</tr>'

            . '</table>'

            . '<div class="fiscal-ack-legal">'
            . 'El acuse de recibo que se declara en este acto, '
            . 'de acuerdo a lo dispuesto en la letra b) del Art. 4°, '
            . 'y la letra c) del Art. 5° de la Ley 19.983, '
            . 'acredita que la entrega de mercaderías o servicio(s) '
            . 'prestado(s) ha(n) sido recibido(s).'
            . '</div>'

            . '</section>';
    }

    private function escape(
        string $value
    ): string {
        return htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}