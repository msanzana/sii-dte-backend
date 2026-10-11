<?php

namespace App\Modules\Dte\Infrastructure\Pdf;

use App\Modules\Dte\Domain\Enums\PrintFormat;
use App\Modules\Dte\Domain\RendererContracts\DtePdfDocumentRendererInterface;
use App\Modules\Dte\Domain\ValueObjects\DtePdfDocumentRenderResult;
use App\Modules\Dte\Domain\ValueObjects\DtePrintableCopy;
use App\Modules\Dte\Domain\ValueObjects\DtePrintablePackage;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Modules\Dte\Domain\Enums\PrintCopyType;
use App\Modules\Dte\Infrastructure\Pdf\Components\DteFiscalPrintHtmlBuilder;

final class DompdfDteTicket58Renderer implements DtePdfDocumentRendererInterface
{
    private const PAPER_WIDTH_MM = 58.0;

    public function render(
        DtePrintablePackage $package
    ): DtePdfDocumentRenderResult {
        if ($package->format() !== PrintFormat::TICKET_58) {
            throw new \DomainException(
                'DompdfDteTicket58Renderer solo admite formato Ticket 58.'
            );
        }

        $options = new Options();

        $options->set(
            'defaultFont',
            'DejaVu Sans'
        );

        $options->set(
            'isRemoteEnabled',
            false
        );

        $options->set(
            'isPhpEnabled',
            false
        );

        $options->set(
            'isJavascriptEnabled',
            false
        );

        $dompdf =
            new Dompdf(
                $options
            );

        $pageHeightMm =
            $this->calculatePageHeightMm(
                $package
            );

        $dompdf->setPaper([
            0,
            0,
            $this->mmToPoints(
                self::PAPER_WIDTH_MM
            ),
            $this->mmToPoints(
                $pageHeightMm
            ),
        ]);

        $dompdf->loadHtml(
            $this->buildHtml(
                $package
            ),
            'UTF-8'
        );

        $dompdf->render();

        return new DtePdfDocumentRenderResult(
            pdfBytes:
                $dompdf->output(),

            format:
                $package->format(),

            pageCount:
                $dompdf
                    ->getCanvas()
                    ->get_page_count(),
        );
    }

    private function calculatePageHeightMm(
        DtePrintablePackage $package
    ): float {
        $document =
            $package->document();

        $hasLogo =
            $document
                ->issuer()
                ->logoDataUri() !== null;

        $pdf417HeightMm =
            $package
                ->pdf417()
                ->physicalMetrics()
                ->totalHeightMm();

        $hasCedible = false;

        foreach (
            $package->copies()
            as $copy
        ) {
            if (
                $copy->type()
                === PrintCopyType::CEDIBLE
            ) {
                $hasCedible = true;
                break;
            }
        }

        /*
        * En 58 mm el texto envuelve más líneas.
        * El Total en palabras y, especialmente, el Acuse
        * necesitan más altura que en Ticket 80.
        */
        $estimatedHeightMm =
            155.0
            + ($hasLogo ? 15.0 : 0.0)
            + count($document->items()) * 10.0
            + count($document->references()) * 8.0
            + 15.0
            + ($hasCedible ? 70.0 : 0.0);

        return max(
            $hasCedible
                ? 300.0
                : 230.0,
            $estimatedHeightMm
        );
    }

    private function buildHtml(
        DtePrintablePackage $package
    ): string {
        $copies = [];

        foreach (
            $package->copies()
            as $copy
        ) {
            for (
                $number = 1;
                $number <= $copy->quantity();
                $number++
            ) {
                $copies[] =
                    '<section class="ticket-page">'
                    . $this->buildCopy(
                        $package,
                        $copy
                    )
                    . '</section>';
            }
        }

        return
            '<!DOCTYPE html>'
            . '<html lang="es">'
            . '<head>'
            . '<meta charset="UTF-8">'
            . '<style>'
            . $this->css()
            . '</style>'
            . '</head>'
            . '<body>'
            . implode(
                '<div class="page-break"></div>',
                $copies
            )
            . '</body>'
            . '</html>';
    }

    private function buildCopy(
        DtePrintablePackage $package,
        DtePrintableCopy $copy
    ): string {
        $document =
            $package->document();

        $issuer =
            $document->issuer();

        $receiver =
            $document->receiver();

        $totals =
            $document->totals();
        $fiscalBuilder =
            new DteFiscalPrintHtmlBuilder();

        $issuerName =
            $issuer->tradeName();

        if (
            $issuerName === null
            || trim($issuerName) === ''
        ) {
            $issuerName =
                $issuer->legalName();
        }

        $html =
            $this->buildIssuerLogoHtml(
                $issuer->logoDataUri()
            )
            . '<div class="issuer-name">'
            . $this->escape(
                $issuerName
            )
            . '</div>'

            . '<div class="center legal-name">'
            . $this->escape(
                $issuer->legalName()
            )
            . '</div>'

            . '<div class="center">'
            . 'RUT '
            . $this->escape(
                $issuer->rut()
            )
            . '</div>';

        if (
            $issuer->giro() !== null
            && trim($issuer->giro()) !== ''
        ) {
            $html .=
                '<div class="center small">'
                . $this->escape(
                    $issuer->giro()
                )
                . '</div>';
        }

        $html .=
            '<div class="center small">'
            . $this->escape(
                $issuer->address()
            )
            . '</div>'

            . '<div class="center small">'
            . $this->escape(
                $issuer->comune()
            )
            . ' - '
            . $this->escape(
                $issuer->city()
            )
            . '</div>'

            . '<div class="separator"></div>'

            . '<div class="dte-type">'
            . $this->escape(
                $this->dteTypeName(
                    $document->dteType()
                )
            )
            . '</div>'

            . '<div class="folio">'
            . 'N° '
            . $this->escape(
                (string)
                $document->folio()
            )
            . '</div>'

            . '<div class="center sii">'
            . $this->escape(
                $this->siiOfficeLabel(
                    $issuer->siiOfficeName()
                )
            )
            . '</div>';

        $legend =
            $copy->legend()
            ?? mb_strtoupper(
                $copy->name(),
                'UTF-8'
            );

        $html .=
            '<div class="copy-legend">'
            . $this->escape(
                $legend
            )
            . '</div>'

            . '<div class="separator"></div>'

            . '<div>'
            . '<strong>Fecha:</strong> '
            . $this->escape(
                $this->formatDate(
                    $document->issueDate()
                )
            )
            . '</div>'

            . '<div>'
            . '<strong>RUT receptor:</strong> '
            . $this->escape(
                $receiver->document()
            )
            . '</div>'

            . '<div>'
            . '<strong>Señor(es):</strong> '
            . $this->escape(
                $receiver->name()
            )
            . '</div>';

        if (
            $receiver->giro() !== null
            && trim($receiver->giro()) !== ''
        ) {
            $html .=
                '<div>'
                . '<strong>Giro:</strong> '
                . $this->escape(
                    $receiver->giro()
                )
                . '</div>';
        }

        if (
            $receiver->address() !== null
            && trim($receiver->address()) !== ''
        ) {
            $html .=
                '<div>'
                . '<strong>Dirección:</strong> '
                . $this->escape(
                    $receiver->address()
                )
                . '</div>';
        }

        $location =
            array_values(
                array_filter(
                    [
                        $receiver->comune(),
                        $receiver->city(),
                    ],
                    static fn (
                        ?string $value
                    ): bool =>
                        $value !== null
                        && trim($value) !== ''
                )
            );

        if ($location !== []) {
            $html .=
                '<div>'
                . '<strong>Ubicación:</strong> '
                . $this->escape(
                    implode(
                        ' - ',
                        $location
                    )
                )
                . '</div>';
        }

        $html .=
            '<div class="separator"></div>'

            . '<div class="section-title">'
            . 'DETALLE'
            . '</div>';

        foreach (
            $document->items()
            as $item
        ) {
            $html .=
                '<div class="item">'
                . '<div class="item-name">'
                . $this->escape(
                    $item->name()
                )
                . '</div>';

            if (
                $item->itemCode() !== null
                && trim($item->itemCode()) !== ''
            ) {
                $html .=
                    '<div class="muted">'
                    . 'Código: '
                    . $this->escape(
                        $item->itemCode()
                    )
                    . '</div>';
            }

            if (
                $item->description() !== null
                && trim(
                    $item->description()
                ) !== ''
            ) {
                $html .=
                    '<div class="muted">'
                    . $this->escape(
                        $item->description()
                    )
                    . '</div>';
            }

            if ($item->taxExempt()) {
                $html .=
                    '<div class="muted strong">'
                    . 'EXENTO'
                    . '</div>';
            }

            $html .=
                '<table class="item-values">'
                . '<tr>'
                . '<td>'
                . $this->decimal(
                    $item->quantity()
                )
                . ' x $ '
                . $this->money(
                    $item->unitPrice()
                )
                . '</td>'
                . '<td>'
                . '$ '
                . $this->money(
                    $item->lineAmount()
                )
                . '</td>'
                . '</tr>'
                . '</table>';

            if (
                $item->discountPercent() != 0.0
                || $item->discountAmount() != 0.0
            ) {
                $html .=
                    '<div class="muted">'
                    . 'Descuento: ';

                $discountParts = [];

                if (
                    $item->discountPercent()
                    != 0.0
                ) {
                    $discountParts[] =
                        $this->decimal(
                            $item->discountPercent()
                        )
                        . '%';
                }

                if (
                    $item->discountAmount()
                    != 0.0
                ) {
                    $discountParts[] =
                        '$ '
                        . $this->money(
                            $item->discountAmount()
                        );
                }

                $html .=
                    implode(
                        ' · ',
                        $discountParts
                    )
                    . '</div>';
            }

            $html .=
                '</div>';
        }

        $html .=
            '<div class="separator"></div>';

        if (
            $document->references() !== []
        ) {
            $html .=
                '<div class="section-title">'
                . 'REFERENCIAS'
                . '</div>';

            foreach (
                $document->references()
                as $reference
            ) {
                $html .=
                    '<div class="reference">'
                    . '#'
                    . $this->escape(
                        (string)
                        $reference->lineNumber()
                    );

                if (
                    $reference
                        ->referencedDteType()
                    !== null
                ) {
                    $html .=
                        ' · TD '
                        . $this->escape(
                            (string)
                            $reference
                                ->referencedDteType()
                        );
                }

                if (
                    $reference
                        ->referencedFolio()
                    !== null
                ) {
                    $html .=
                        ' · Folio '
                        . $this->escape(
                            (string)
                            $reference
                                ->referencedFolio()
                        );
                }

                if (
                    $reference
                        ->referencedIssueDate()
                    !== null
                ) {
                    $html .=
                        '<br>'
                        . $this->escape(
                            $this->formatDate(
                                $reference
                                    ->referencedIssueDate()
                            )
                        );
                }

                if (
                    $reference
                        ->referenceCode()
                    !== null
                ) {
                    $html .=
                        ' · Código '
                        . $this->escape(
                            $reference
                                ->referenceCode()
                        );
                }

                if (
                    $reference->reason()
                    !== null
                ) {
                    $html .=
                        '<br>'
                        . $this->escape(
                            $reference->reason()
                        );
                }

                $html .=
                    '</div>';
            }

            $html .=
                '<div class="separator"></div>';
        }

        $html .=
            '<table class="totals">'
            . '<tr>'
            . '<td>Neto</td>'
            . '<td>$ '
            . $this->money(
                $totals->net()
            )
            . '</td>'
            . '</tr>';

        if (
            $totals->exempt() != 0.0
        ) {
            $html .=
                '<tr>'
                . '<td>Exento</td>'
                . '<td>$ '
                . $this->money(
                    $totals->exempt()
                )
                . '</td>'
                . '</tr>';
        }

        $html .=
            '<tr>'
            . '<td>IVA</td>'
            . '<td>$ '
            . $this->money(
                $totals->tax()
            )
            . '</td>'
            . '</tr>'

            . '<tr class="total">'
            . '<td>TOTAL</td>'
            . '<td>$ '
            . $this->money(
                $totals->total()
            )
            . '</td>'
            . '</tr>'
            . '</table>';

        $html .=
            $fiscalBuilder->renderTotalInWords(
                $totals->totalInWords()
            );

        $html .=
            $fiscalBuilder->renderAcknowledgement(
                $copy
            );

        $html .=
            $this->buildPdf417(
                $package
            );

        if (
            $issuer->resolutionNumber()
            !== null
            || $issuer->resolutionDate()
            !== null
        ) {
            $html .=
                '<div class="resolution">'
                . 'Resolución SII N° '
                . $this->escape(
                    $issuer->resolutionNumber()
                    ?? '-'
                )
                . ' de '
                . $this->escape(
                    $this->formatDate(
                        $issuer->resolutionDate()
                    )
                )
                . '</div>';
        }

        return $html;
    }
    private function buildIssuerLogoHtml(
        ?string $logoDataUri
    ): string {
        if (!$this->isSupportedLogoDataUri($logoDataUri)) {
            return '';
        }

        return
            '<div style="text-align:center;margin:0 0 1.5mm 0;">'
            . '<img src="'
            . $this->escape($logoDataUri)
            . '" alt="" style="max-width:24mm;max-height:12mm;width:auto;height:auto;">'
            . '</div>';
    }

    private function isSupportedLogoDataUri(
        ?string $logoDataUri
    ): bool {
        if (
            $logoDataUri === null
            || trim($logoDataUri) === ''
        ) {
            return false;
        }

        return
            str_starts_with(
                $logoDataUri,
                'data:image/png;base64,'
            )
            || str_starts_with(
                $logoDataUri,
                'data:image/jpeg;base64,'
            );
    }
    private function buildPdf417(
        DtePrintablePackage $package
    ): string {
        $selection =
            $package->pdf417();

        $renderResult =
            $selection->renderResult();

        $metrics =
            $selection->physicalMetrics();

        $source =
            'data:'
            . $renderResult->mimeType()
            . ';base64,'
            . base64_encode(
                $renderResult->svg()
            );

        return
            '<div class="ted">'
            . '<div class="barcode" style="'
            . 'padding:'
            . $this->cssNumber(
                $metrics->quietZoneMm()
            )
            . 'mm;">'

            . '<img'
            . ' src="'
            . $source
            . '"'
            . ' alt="PDF417 TED"'
            . ' style="'
            . 'display:block;'
            . 'width:'
            . $this->cssNumber(
                $metrics->symbolWidthMm()
            )
            . 'mm;'
            . 'height:'
            . $this->cssNumber(
                $metrics->symbolHeightMm()
            )
            . 'mm;"'
            . '>'

            . '</div>'

            . '<div class="ted-caption">'
            . 'Timbre Electrónico SII'
            . '</div>'

            . '</div>';
    }

    private function dteTypeName(
        int $dteType
    ): string {
        return match ($dteType) {
            33 =>
                'FACTURA ELECTRÓNICA',

            34 =>
                'FACTURA EXENTA ELECTRÓNICA',

            39 =>
                'BOLETA ELECTRÓNICA',

            41 =>
                'BOLETA EXENTA ELECTRÓNICA',

            46 =>
                'FACTURA DE COMPRA ELECTRÓNICA',

            52 =>
                'GUÍA DE DESPACHO ELECTRÓNICA',

            56 =>
                'NOTA DE DÉBITO ELECTRÓNICA',

            61 =>
                'NOTA DE CRÉDITO ELECTRÓNICA',

            default =>
                'DOCUMENTO TRIBUTARIO ELECTRÓNICO',
        };
    }
    private function siiOfficeLabel(
        ?string $officeName
    ): string {
        if (
            $officeName === null
            || trim($officeName) === ''
        ) {
            return 'S.I.I.';
        }

        return
            'S.I.I. - '
            . mb_strtoupper(
                trim($officeName),
                'UTF-8'
            );
    }
    private function money(
        float $value
    ): string {
        return number_format(
            $value,
            0,
            ',',
            '.'
        );
    }

    private function decimal(
        float $value
    ): string {
        $formatted =
            number_format(
                $value,
                6,
                ',',
                '.'
            );

        return rtrim(
            rtrim(
                $formatted,
                '0'
            ),
            ','
        );
    }

    private function formatDate(
        ?string $date
    ): string {
        if (
            $date === null
            || trim($date) === ''
        ) {
            return '';
        }

        $parsed =
            \DateTimeImmutable::createFromFormat(
                'Y-m-d',
                $date
            );

        return $parsed === false
            ? $date
            : $parsed->format(
                'd-m-Y'
            );
    }

    private function escape(
        ?string $value
    ): string {
        return htmlspecialchars(
            $value ?? '',
            ENT_QUOTES
            | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }

    private function cssNumber(
        float $value
    ): string {
        return rtrim(
            rtrim(
                number_format(
                    $value,
                    5,
                    '.',
                    ''
                ),
                '0'
            ),
            '.'
        );
    }

    private function mmToPoints(
        float $millimeters
    ): float {
        return
            $millimeters
            / 25.4
            * 72.0;
    }

    private function css(): string
    {
        return <<<'CSS'
@page {
    margin: 4mm;
}

html,
body {
    margin: 0;
    padding: 0;
    font-family: "DejaVu Sans", sans-serif;
    font-size: 7pt;
    color: #111;
}

.ticket-page {
    width: 100%;
    page-break-inside: avoid;
}

.page-break {
    page-break-after: always;
}

.center {
    text-align: center;
}

.small {
    font-size: 6pt;
}

.muted {
    font-size: 5.8pt;
}

.strong {
    font-weight: bold;
}

.legal-name {
    font-size: 6.5pt;
}

.issuer-name {
    text-align: center;
    font-size: 10pt;
    font-weight: bold;
    margin-bottom: 1mm;
}

.separator {
    border-top: 0.5pt dashed #444;
    margin: 2mm 0;
}

.dte-type {
    text-align: center;
    font-weight: bold;
    font-size: 8pt;
}

.folio {
    text-align: center;
    font-weight: bold;
    font-size: 11pt;
    margin-top: 1mm;
}

.sii {
    font-weight: bold;
    margin-top: 1mm;
}

.copy-legend {
    text-align: center;
    font-size: 8pt;
    font-weight: bold;
    margin: 2mm 0;
}

.section-title {
    text-align: center;
    font-size: 6.5pt;
    font-weight: bold;
    margin-bottom: 1mm;
}

.item {
    padding: 1.5mm 0;
    border-bottom: 0.3pt solid #ccc;
    page-break-inside: avoid;
}

.item-name {
    font-weight: bold;
    font-size: 7pt;
}

.item-values {
    width: 100%;
    border-collapse: collapse;
    margin-top: 0.8mm;
}

.item-values td {
    padding: 0;
    font-size: 6.5pt;
}

.item-values td:last-child {
    text-align: right;
    font-weight: bold;
}

.reference {
    font-size: 6pt;
    margin-bottom: 1mm;
}

.totals {
    width: 100%;
    border-collapse: collapse;
    margin-top: 2mm;
    page-break-inside: avoid;
}

.totals td {
    padding: 0.8mm 0;
}

.totals td:last-child {
    text-align: right;
}

.totals .total td {
    border-top: 0.8pt solid #111;
    font-size: 9.5pt;
    font-weight: bold;
    padding-top: 1.5mm;
}

.ted {
    text-align: center;
    margin-top: 4mm;
    page-break-inside: avoid;
}

.barcode {
    display: inline-block;
    background: #fff;
    line-height: 0;
}

.ted-caption {
    font-weight: bold;
    font-size: 6.5pt;
    margin-top: 1mm;
}

.resolution {
    text-align: center;
    font-size: 5.8pt;
    margin-top: 2mm;
}
.fiscal-total-in-words {
    margin-top: 2mm;
    margin-bottom: 2mm;
    font-size: 6.2pt;
    line-height: 1.25;
    page-break-inside: avoid;
}

.fiscal-acknowledgement {
    border: 0.6pt solid #111;
    padding: 1.5mm;
    margin-top: 2mm;
    margin-bottom: 2mm;
    page-break-inside: avoid;
}

.fiscal-ack-title {
    text-align: center;
    font-size: 6.8pt;
    font-weight: bold;
    margin-bottom: 1.2mm;
}

.fiscal-ack-fields {
    width: 100%;
    border-collapse: collapse;
}

.fiscal-ack-fields td {
    vertical-align: bottom;
    padding: 0.8mm;
    font-size: 5.8pt;
}

.fiscal-ack-half {
    width: 50%;
}

.fiscal-ack-fill {
    display: block;
    min-height: 3mm;
    border-bottom: 0.5pt solid #111;
    margin-top: 0.6mm;
}

.fiscal-ack-signature .fiscal-ack-fill {
    min-height: 6mm;
}

.fiscal-ack-legal {
    font-size: 4.8pt;
    line-height: 1.18;
    text-align: justify;
    margin-top: 1.2mm;
}
CSS;
    }
}