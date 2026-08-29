<?php
namespace App\Modules\Dte\Infrastructure\Xml;
use DOMDocument;
use DOMElement;
use RuntimeException;

class DteXmlBuilderService
{
    private const NS_SII_DTE = 'http://www.sii.cl/SiiDte';
    // private const XMLDSIG_NS =
    //     'http://www.w3.org/2000/09/xmldsig#';

    private const XSI_NS =
        'http://www.w3.org/2001/XMLSchema-instance';
    public function build(
        array $data
    ): string
    {

        $dom = new DOMDocument(
            '1.0',
            'ISO-8859-1'
        );

        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $dte = $dom->createElementNS(self::NS_SII_DTE,'DTE');
        // $dte->setAttributeNS(
        //     'http://www.w3.org/2000/xmlns/',
        //     'xmlns:ds',
        //     self::XMLDSIG_NS
        // );

        $dte->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:xsi',
            self::XSI_NS
        );

        $dte->setAttribute('version', $data['version'] ?? '1.0');
        $dom->appendChild($dte);

        $documento = $this->appendElement($dom,$dte,'Documento');
        $documento->setAttribute('ID', $data['document_xml_id']);

        $encabezado = $this->appendElement($dom,$documento,'Encabezado');

        $this->buildIdDoc($dom, $encabezado, $data['id_doc']);
        $this->buildEmitter($dom, $encabezado, $data['emitter']);
        $this->buildReceiver($dom, $encabezado, $data['receiver']);
        $this->buildTotals($dom, $encabezado, $data['totals']);

        foreach ($data['details'] as $detail)
        {
            $this->buildDetail($dom, $documento, $detail);
        }

        foreach ($data['references'] as $reference)
        {
            $this->buildReference($dom, $documento, $reference);
        }

        $xml = $dom->saveXml();
        if ($xml === false || trim($xml) === '') {
            throw new RuntimeException('No fue posible serializar el XML del DTE.');
        }

        return $xml;
    }

    private function buildIdDoc(DOMDocument $dom, DOMElement $encabezado, array $idDoc): void
    {
        $idDocNode = $this->appendElement($dom, $encabezado, 'IdDoc');

        $this->appendElement($dom, $idDocNode, 'TipoDTE', (string) $idDoc['tipo_dte']);
        $this->appendElement($dom, $idDocNode, 'Folio', (string) $idDoc['folio']);
        $this->appendElement($dom, $idDocNode, 'FchEmis', (string) $idDoc['fecha_emision']);
    }

    private function buildEmitter(DOMDocument $dom, DOMElement $encabezado, array $emitter): void
    {
        $emisor = $this->appendElement($dom, $encabezado, 'Emisor');

        $this->appendElement($dom, $emisor, 'RUTEmisor', (string) $emitter['rut']);
        $this->appendElement($dom, $emisor, 'RznSoc', (string) $emitter['razon_social']);

        if (!empty($emitter['giro'])) {
            $this->appendElement($dom, $emisor, 'GiroEmis', (string) $emitter['giro']);
        }

        /*
        * Correo del emisor.
        *
        * Debe ir antes de Acteco/DirOrigen.
        */
        if (!empty($emitter['email'])) {
            $this->appendElement(
                $dom,
                $emisor,
                'CorreoEmisor',
                (string) $emitter['email']
            );
        }

        /*
        * Actividad económica SII.
        *
        * Es la pieza que actualmente falta.
        */
        if (
            empty($emitter['acteco'])
        ) {
            throw new RuntimeException(
                'La empresa no tiene configurado el código de actividad económica SII requerido para generar el DTE.'
            );
        }

        $this->appendElement(
            $dom,
            $emisor,
            'Acteco',
            (string) $emitter['acteco']
        );
        $this->appendElement($dom, $emisor, 'DirOrigen', (string) $emitter['direccion']);
        $this->appendElement($dom, $emisor, 'CmnaOrigen', (string) $emitter['commune']);
        $this->appendElement($dom, $emisor, 'CiudadOrigen', (string) $emitter['city']);
    }

    private function buildReceiver(DOMDocument $dom, DOMElement $encabezado, array $receiver): void
    {
        $receptor = $this->appendElement($dom, $encabezado, 'Receptor');

        $this->appendElement($dom, $receptor, 'RUTRecep', (string) $receiver['rut']);
        $this->appendElement($dom, $receptor, 'RznSocRecep', (string) $receiver['razon_social']);

        if (!empty($receiver['giro'])) {
            $this->appendElement($dom, $receptor, 'GiroRecep', (string) $receiver['giro']);
        }

        if (!empty($receiver['direccion'])) {
            $this->appendElement($dom, $receptor, 'DirRecep', (string) $receiver['direccion']);
        }

        if (!empty($receiver['commune'])) {
            $this->appendElement($dom, $receptor, 'CmnaRecep', (string) $receiver['commune']);
        }

        if (!empty($receiver['city'])) {
            $this->appendElement($dom, $receptor, 'CiudadRecep', (string) $receiver['city']);
        }
    }

    private function buildTotals(DOMDocument $dom, DOMElement $encabezado, array $totals): void
    {
        $totales = $this->appendElement($dom, $encabezado, 'Totales');

        if ($totals['net_amount'] !== null) {
            $this->appendElement($dom, $totales, 'MntNeto', (string) $totals['net_amount']);
        }

        if ($totals['exempt_amount'] !== null) {
            $this->appendElement($dom, $totales, 'MntExe', (string) $totals['exempt_amount']);
        }

        if ($totals['tax_rate'] !== null) {
            $this->appendElement($dom, $totales, 'TasaIVA', (string) $totals['tax_rate']);
        }

        if ($totals['tax_amount'] !== null) {
            $this->appendElement($dom, $totales, 'IVA', (string) $totals['tax_amount']);
        }

        $this->appendElement($dom, $totales, 'MntTotal', (string) $totals['total_amount']);
    }

    private function buildDetail(DOMDocument $dom, DOMElement $documento, array $detail): void
    {
        $detalle = $this->appendElement($dom, $documento, 'Detalle');

        $this->appendElement($dom, $detalle, 'NroLinDet', (string) $detail['line_number']);

        if (!empty($detail['item_code_type']) && !empty($detail['item_code'])) {
            $cdgItem = $this->appendElement($dom, $detalle, 'CdgItem');
            $this->appendElement($dom, $cdgItem, 'TpoCodigo', (string) $detail['item_code_type']);
            $this->appendElement($dom, $cdgItem, 'VlrCodigo', (string) $detail['item_code']);
        }

        if (!empty($detail['tax_exempt'])) {
            $this->appendElement($dom, $detalle, 'IndExe', '1');
        }

        $this->appendElement($dom, $detalle, 'NmbItem', (string) $detail['name']);

        if (!empty($detail['description'])) {
            $this->appendElement($dom, $detalle, 'DscItem', (string) $detail['description']);
        }

        $this->appendElement($dom, $detalle, 'QtyItem', (string) $detail['quantity']);
        $this->appendElement($dom, $detalle, 'PrcItem', (string) $detail['unit_price']);
        $this->appendElement($dom, $detalle, 'MontoItem', (string) $detail['line_amount']);
    }

    private function buildReference(DOMDocument $dom, DOMElement $documento, array $reference): void
    {
        $referencia = $this->appendElement($dom, $documento, 'Referencia');

        $this->appendElement($dom, $referencia, 'NroLinRef', (string) $reference['line_number']);

        if ($reference['referenced_dte_type'] !== null) {
            $this->appendElement($dom, $referencia, 'TpoDocRef', (string) $reference['referenced_dte_type']);
        }

        if ($reference['referenced_folio'] !== null) {
            $this->appendElement($dom, $referencia, 'FolioRef', (string) $reference['referenced_folio']);
        }

        if ($reference['referenced_issue_date'] !== null) {
            $this->appendElement($dom, $referencia, 'FchRef', (string) $reference['referenced_issue_date']);
        }

        if ($reference['reference_code'] !== null) {
            $this->appendElement($dom, $referencia, 'CodRef', (string) $reference['reference_code']);
        }

        if ($reference['reason'] !== null && trim($reference['reason']) !== '') {
            $this->appendElement($dom, $referencia, 'RazonRef', (string) $reference['reason']);
        }
    }

    private function appendElement(
        DOMDocument $dom,
        DOMElement $parent,
        string $name,
        ?string $value = null
    ): DOMElement {
        $element = $dom->createElementNS(self::NS_SII_DTE, $name);

        if ($value !== null) {
            $element->appendChild($dom->createTextNode($value));
        }

        $parent->appendChild($element);

        return $element;
    }
}

