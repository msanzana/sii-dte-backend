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
    private const NS_XSI = 'http://www.w3.org/2001/XMLSchema-instance';
    private const NS_XMLNS = 'http://www.w3.org/2000/xmlns/';
    private const ENVIO_BOLETA_SCHEMA_LOCATION =
        'http://www.sii.cl/SiiDte EnvioBOLETA_v11.xsd';
    public function __construct(
        private readonly XmlDsigIntegrityService $integrityService
    ) {}
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

        $envioNode = $envioDom->createElementNS(
            self::NS_SII_DTE,
            'EnvioBOLETA'
        );

        $envioNode->setAttributeNS(
            self::NS_XMLNS,
            'xmlns:xsi',
            self::NS_XSI
        );

        $envioNode->setAttributeNS(
            self::NS_XSI,
            'xsi:schemaLocation',
            self::ENVIO_BOLETA_SCHEMA_LOCATION
        );

        $envioNode->setAttribute(
            'version',
            '1.0'
        );

        $envioDom->appendChild(
            $envioNode
        );

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

        /*
        |--------------------------------------------------------------------------
        | Incorporar el DTE firmado sin importNode()
        |--------------------------------------------------------------------------
        |
        | El DTE ya contiene una firma XMLDSig válida.
        | No debemos importarlo a otro DOMDocument porque LIBXML puede
        | reorganizar namespaces y alterar la canonicalización del Documento.
        |
        */

        $placeholder =
            'SIGNED_DTE_XML_PLACEHOLDER';

        $setNode->appendChild(
            $envioDom->createComment(
                $placeholder
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Serializar primero la estructura base de EnvioBOLETA
        |--------------------------------------------------------------------------
        */

        $xml =
            $envioDom->saveXML();

        if (
            $xml === false
            || trim($xml) === ''
        ) {
            throw SiiBoletaSendException::because(
                'No fue posible serializar el payload XML de EnvioBOLETA.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Quitar únicamente la declaración XML del DTE firmado
        |--------------------------------------------------------------------------
        */

        $signedDteBody =
            preg_replace(
                '/^\s*<\?xml[^?]*\?>\s*/i',
                '',
                $signedXml,
                1
            );

        if (
            $signedDteBody === null
            || trim($signedDteBody) === ''
        ) {
            throw SiiBoletaSendException::because(
                'No fue posible preparar el DTE firmado para incorporarlo al EnvioBOLETA.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Localizar el placeholder
        |--------------------------------------------------------------------------
        */

        $marker =
            '<!--'
            . $placeholder
            . '-->';

        if (
            !str_contains(
                $xml,
                $marker
            )
        ) {
            throw SiiBoletaSendException::because(
                'No fue posible localizar el marcador del DTE firmado en EnvioBOLETA.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Insertar textualmente el DTE firmado
        |--------------------------------------------------------------------------
        |
        | No usamos importNode().
        | De esta forma conservamos exactamente la serialización y namespaces
        | con los que fue calculado el DigestValue del Documento.
        |
        */

        $xml =
            str_replace(
                $marker,
                $signedDteBody,
                $xml
            );

        /*
        |--------------------------------------------------------------------------
        | Verificar que la firma interna haya sobrevivido al wrapping
        |--------------------------------------------------------------------------
        |
        | En este punto todavía no existe la firma externa de SetDTE.
        |
        */

        $this->integrityService
    ->assertAllSignaturesValid(
        $xml
    );

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
