<?php
namespace App\Modules\Dte\Infrastructure\Xml;

use App\Modules\Dte\Domain\Entities\Company;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Exceptions\InvalidSignatureXmlException;
use DOMDocument;
use DOMElement;
use DOMXPath;


class EnvioDteEnvelopeBuilderService
{
    private const NS_SII_DTE = 'http://www.sii.cl/SiiDte';

    public function build(
        DteDocument $document,
        Company $company,
        string $signedDteXml
    ):array
    {
        $signedDteDom = new DOMDocument('1.0','ISO-8859-1');
        $signedDteDom->preserveWhiteSpace = false;
        $signedDteDom->formatOutput = true;

        $loaded = @$signedDteDom->loadXML($signedDteXml);

        if(!$loaded)
        {
            throw InvalidSignatureXmlException::because(
                'No fue posible cargar el XML firmado del DTE para construir el EnvioDTE.'
            );
        }

        $signedDteXPath = new DOMXPath($signedDteDom);

        $dteNode = $signedDteXPath->query("*/*[local-name()='DTE']")->item(0);

        if (!$dteNode instanceof DOMElement) {
            throw InvalidSignatureXmlException::because(
                'No se encontró el nodo DTE dentro del XML firmado del documento.'
            );
        }

        $senderRutBody = trim((string) config('dte.sii.sender.rut_body'));
        $senderRutDv = trim((string) config('dte.sii.sender.rut_dv'));

        if ($senderRutBody === '' || $senderRutDv === '') {
            throw InvalidSignatureXmlException::because(
                'Falta configurar DTE_SII_SENDER_RUT_BODY o DTE_SII_SENDER_RUT_DV.'
            );
        }

        $receiverRut = trim((string) config('dte.sii.receiver_rut', '60803000-K'));

        $envioDom = new DOMDocument('1.0', 'ISO-8859-1');
        $envioDom->preserveWhiteSpace = false;
        $envioDom->formatOutput = true;

        $envioDte = $envioDom->createElementNS(self::NS_SII_DTE, 'EnvioDTE');
        $envioDte->setAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation',
            'http://www.sii.cl/SiiDte EnvioDTE_v10.xsd'
        );
        $envioDte->setAttribute('version', '1.0');
        $envioDom->appendChild($envioDte);

        $setDteId = 'SetDoc_' . $document->id();
        $setDte = $envioDom->createElementNS(self::NS_SII_DTE, 'SetDTE');
        $setDte->setAttribute('ID', $setDteId);
        $envioDte->appendChild($setDte);

        $caratula = $envioDom->createElementNS(self::NS_SII_DTE, 'Caratula');
        $caratula->setAttribute('version', '1.0');
        $setDte->appendChild($caratula);

        $this->appendElement($envioDom, $caratula, 'RutEmisor', $company->rut());
        $this->appendElement($envioDom, $caratula, 'RutEnvia', $senderRutBody . '-' . $senderRutDv);
        $this->appendElement($envioDom, $caratula, 'RutReceptor', $receiverRut);
        $this->appendElement($envioDom, $caratula, 'FchResol', (string) $company->resolutionDate());
        $this->appendElement($envioDom, $caratula, 'NroResol', (string) $company->resolutionNumber());
        $this->appendElement($envioDom, $caratula, 'TmsFirmaEnv', now()->format('Y-m-d\TH:i:s'));

        $subTotDte = $envioDom->createElementNS(self::NS_SII_DTE, 'SubTotDTE');
        $this->appendElement($envioDom, $subTotDte, 'TpoDTE', (string) $document->dteType()->value);
        $this->appendElement($envioDom, $subTotDte, 'NroDTE', '1');
        $caratula->appendChild($subTotDte);

        $importedDte = $envioDom->importNode($dteNode, true);
        $setDte->appendChild($importedDte);

        $xml = $envioDom->saveXML();

        if ($xml === false || $xml === '') {
            throw InvalidSignatureXmlException::because(
                'No fue posible serializar el XML del EnvioDTE.'
            );
        }

        return [
            'set_dte_id' => $setDteId,
            'envelope_xml' => $xml,
        ];
    }

    private function appendElement(
        DOMDocument $dom,
        DOMElement $parent,
        string $name,
        string $value
    ): void {
        $node = $dom->createElementNS(self::NS_SII_DTE, $name);
        $node->appendChild($dom->createTextNode($value));
        $parent->appendChild($node);
    }

}
