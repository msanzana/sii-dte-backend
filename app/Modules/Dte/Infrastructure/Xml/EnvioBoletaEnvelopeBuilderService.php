<?php
namespace App\Modules\Dte\Infrastructure\Xml;


use App\Modules\Dte\Domain\Entities\Company;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Exceptions\SiiBoletaSendException;
use DOMDocument;
use DOMElement;
use DOMXPath;

class EnvioBoletaEnvelopeBuilderService
{
    private const NS_SII_DTE = 'http://www.sii.cl/SiiDte';

    public function build(
        DteDocument $document,
        Company $company,
        string $signedXml
    ):array{
        $wrap = filter_var(config('dte.sii.boleta.wrap_in_envio', true), FILTER_VALIDATE_BOOLEAN);

        if(!$wrap)
        {
            return [
                'request_identifier' => 'BOLETA:'.$document->id()
            ];
        }
        $signedDom = new DOMDocument('1.0','ISO-8859-1');
        $signedDom->preserveWhiteSpace = true;
        $signedDom->formatOutput = false;

        $loaded = @$signedDom->loadXml($signedXml);

        if(!$loaded)
        {
            throw SiiBoletaSendException::because(
                'No fue posible cargar el XML firmado de la boleta para construir el sobre de envío.'
            );
        }

        //$xpath = new DOMXPath($signedDom);
        $dteNode = $signedDom->documentElement;

        if (
            !$dteNode instanceof DOMElement
            || $dteNode->localName !== 'DTE'
        ) {
            throw SiiBoletaSendException::because(
                'El elemento raíz del XML firmado de la boleta no corresponde a DTE.'
            );
        }

        $senderRutBody= trim((string) config('dte.sii.sender.rut_body'));
        $senderRutDv = trim((string) config('dte.sii.sender.rut_dv'));
        $receiverRut = trim((string) config('dte.sii.receiver_rut', '60803000-K'));

        if($senderRutBody === '' || $senderRutDv === '')
        {
            throw SiiBoletaSendException::because(
                'Falta configurar DTE_SII_SENDER_RUT_BODY o DTE_SII_SENDER_RUT_DV para boleta.'
            );
        }

        $envioDom = new DOMDocument('1.0', 'ISO-8859-1');
        $envioDom->preserveWhiteSpace = false;
        $envioDom->formatOutput = true;

        $envioNode = $envioDom->createElementNS(self::NS_SII_DTE, 'EnvioBOLETA');
        $envioNode->setAttribute('version', '1.0');
        $envioDom->appendChild($envioNode);

        $setBoletaId = 'SetBoleta_'.$document->id();

        $setNode = $envioDom->createElementNS(self::NS_SII_DTE, 'SetDTE');
        $setNode->setAttribute('ID', $setBoletaId);
        $envioNode->appendChild($setNode);

        $caratula = $envioDom->createElementNS(self::NS_SII_DTE, 'Caratula');
        $caratula->setAttribute('version','1.0');
        $setNode->appendChild($caratula);

        $this->appendElement($envioDom,$caratula,'RutEmisor',$company->rut());
        $this->appendElement($envioDom,$caratula,'RutEnvia',$senderRutBody.'-'.$senderRutDv);
        $this->appendElement($envioDom, $caratula, 'RutReceptor', $receiverRut);
        $this->appendElement($envioDom, $caratula, 'FchResol', (string )$company->resolutionDate());
        $this->appendElement($envioDom, $caratula, 'NroResol', (string) $company->resolutionNumber());
        $this->appendElement($envioDom, $caratula, 'TmstFirmaEnv', now()->format('Y-m-d\TH:i:s'));

        $SubTotDte = $envioDom->createElementNS(self::NS_SII_DTE, 'SubTotDTE');
        $this->appendElement($envioDom, $SubTotDte, 'TpoDTE', (string) $document->dteType()->value);
        $this->appendElement($envioDom, $SubTotDte, 'NroDTE', '1');
        $caratula->appendChild($SubTotDte);

        $importedDte = $envioDom->importNode($dteNode, true);
        $setNode->appendChild($importedDte);

        $xml = $envioDom->saveXML();

        if($xml === false || $xml === '')
        {
            throw SiiBoletaSendException::because(
                'No fue posible serializar el payload XML de EnvioBOLETA.'
            );
        }

        return [
            'request_identifier' => $setBoletaId,
            'payload_xml' => $xml,
        ];
    }

    private function appendElement(
        DOMDocument $dom,
        DOMElement $parent,
        string $name,
        string $value,
    ): void {
        $node = $dom->createElementNS(self::NS_SII_DTE, $name);
        $node->appendChild($dom->createTextNode($value));
        $parent->appendChild($node);
    }
}
