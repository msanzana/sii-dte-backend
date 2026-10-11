<?php

namespace App\Modules\Dte\Infrastructure\Xml;

use App\Modules\Dte\Domain\Services\TedEncodingService;
use RuntimeException;

class DteTedEmbedderService
{
    public function embedTed(
        string $dteXmlIso88591,
        string $tedXmlUtf8
    ): string {
        $tedXmlIso88591 =
            TedEncodingService::utf8ToLatin1Strict(
                $tedXmlUtf8,
                'XML del TED'
            );

        $positionTmstFirma =
            strpos(
                $dteXmlIso88591,
                '<TmstFirma>'
            );

        if ($positionTmstFirma !== false) {
            return substr(
                $dteXmlIso88591,
                0,
                $positionTmstFirma
            )
            . $tedXmlIso88591
            . PHP_EOL
            . substr(
                $dteXmlIso88591,
                $positionTmstFirma
            );
        }

        $closingDocumento =
            strrpos(
                $dteXmlIso88591,
                '</Documento>'
            );

        if ($closingDocumento === false) {
            throw new RuntimeException(
                'No fue posible localizar el cierre </Documento> para incrustar el TED.'
            );
        }

        return substr(
            $dteXmlIso88591,
            0,
            $closingDocumento
        )
        . $tedXmlIso88591
        . PHP_EOL
        . substr(
            $dteXmlIso88591,
            $closingDocumento
        );
    }
}