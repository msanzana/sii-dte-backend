<?php
namespace App\Modules\Dte\Infrastructure\Xml;

use RuntimeException;

class DteTedEmbedderService
{
    public function embedTed(string $dteXmlIso88591, string $tedXmlUtf8): string
    {
        $tedXmlIso88591 = iconv('UTF-8','ISO-8859-1//TRANSLIT//IGNORE', $tedXmlUtf8);

        if($tedXmlIso88591 === false || $tedXmlIso88591 === '') {
            throw new RuntimeException(
                'No fue posible convertir el TED a ISO-8859-1 antes de incrustarlo en el DTE.'
            );
        }

        $positionTmstFirma = strpos($dteXmlIso88591,'<TmstFirma>');

        if($positionTmstFirma !== false) {
            return substr($dteXmlIso88591,0, $positionTmstFirma)
            .$tedXmlUtf8. PHP_EOL
            .substr($dteXmlIso88591, $positionTmstFirma);
        }

        $closingDocumento = strrpos($dteXmlIso88591,'</Documento>');

        if($closingDocumento === false)
        {
            throw new RuntimeException(
                'No fue posible localizar el cierre </Documento> para incrustar el TED.'
            );
        }

        return substr($dteXmlIso88591,0, $closingDocumento)
                .$tedXmlIso88591 . PHP_EOL
                .substr($dteXmlIso88591, $closingDocumento);

    }
}
