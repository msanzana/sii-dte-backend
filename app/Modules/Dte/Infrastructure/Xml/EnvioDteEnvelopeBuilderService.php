<?php
namespace App\Modules\Dte\Infrastructure\Xml;

use App\Modules\Dte\Domain\Entities\Company;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Exceptions\InvalidSignatureXmlException;
use App\Modules\Dte\Infrastructure\Xml\XmlDsigIntegrityService;
use DOMDocument;
use DOMElement;
use DOMXPath;


class EnvioDteEnvelopeBuilderService
{
    private const NS_SII_DTE = 'http://www.sii.cl/SiiDte';
    private const XMLDSIG_NS ='http://www.w3.org/2000/09/xmldsig#';

    public function __construct(
        private readonly XmlDsigIntegrityService $integrityService
    ) {}
    public function build(
        DteDocument $document,
        Company $company,
        string $signedDteXml
    ):array
    {
        $signedDteDom = new DOMDocument('1.0','ISO-8859-1');
        $signedDteDom->preserveWhiteSpace = true;
        $signedDteDom->formatOutput = false;

        $loaded = @$signedDteDom->loadXML($signedDteXml);

        if(!$loaded)
        {
            throw InvalidSignatureXmlException::because(
                'No fue posible cargar el XML firmado del DTE para construir el EnvioDTE.'
            );
        }

        $dteNode = $signedDteDom->documentElement;

        if (
            !$dteNode instanceof DOMElement
            || $dteNode->localName !== 'DTE'
        ) {
            throw InvalidSignatureXmlException::because(
                'El elemento raíz del XML firmado no corresponde a DTE.'
            );
        }

        /*
        * NUEVA VALIDACIÓN IMPORTANTE:
        *
        * No permitir un DTE generado con
        * http://www.sii.cl/siiDte
        */
        if (
            $dteNode->namespaceURI
            !== self::NS_SII_DTE
        ) {
            throw InvalidSignatureXmlException::because(
                'El DTE firmado utiliza un namespace SII incorrecto. '
                . 'Esperado: '
                . self::NS_SII_DTE
                . '. Recibido: '
                . ($dteNode->namespaceURI ?? 'NULL')
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

        if ($receiverRut === '') {
            throw InvalidSignatureXmlException::because(
                'Falta configurar el RUT receptor del SII.'
            );
        }

        $envioDom = new DOMDocument('1.0', 'ISO-8859-1');
        $envioDom->preserveWhiteSpace = true;
        $envioDom->formatOutput = false;

        $envioDte = $envioDom->createElementNS(self::NS_SII_DTE, 'EnvioDTE');
        // $envioDte->setAttributeNS(
        //     'http://www.w3.org/2000/xmlns/',
        //     'xmlns:ds',
        //     self::XMLDSIG_NS
        // );
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
        $this->appendElement($envioDom, $caratula, 'TmstFirmaEnv', now('America/Santiago')->format('Y-m-d\TH:i:s'));

        $subTotDte = $envioDom->createElementNS(self::NS_SII_DTE, 'SubTotDTE');
        $this->appendElement($envioDom, $subTotDte, 'TpoDTE', (string) $document->dteType()->value);
        $this->appendElement($envioDom, $subTotDte, 'NroDTE', '1');
        $caratula->appendChild($subTotDte);

        //$importedDte = $envioDom->importNode($dteNode, true);
        //$setDte->appendChild($importedDte);

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

        $placeholder = 'SIGNED_DTE_XML_PLACEHOLDER';

        $setDte->appendChild(
            $envioDom->createComment(
                $placeholder
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Serializar primero la estructura base del EnvioDTE
        |--------------------------------------------------------------------------
        */

        $xml = $envioDom->saveXML();

        if (
            $xml === false
            || trim($xml) === ''
        ) {
            throw InvalidSignatureXmlException::because(
                'No fue posible serializar el XML base del EnvioDTE.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Quitar la declaración XML del DTE firmado
        |--------------------------------------------------------------------------
        |
        | signedDteXml contiene algo como:
        |
        | <?xml version="1.0" encoding="ISO-8859-1"?>
        | <DTE>...</DTE>
        |
        | Como el EnvioDTE ya posee su propia declaración XML, debemos eliminar
        | únicamente la declaración inicial del DTE.
        |
        */

        $signedDteBody = preg_replace(
            '/^\s*<\?xml[^?]*\?>\s*/i',
            '',
            $signedDteXml,
            1
        );

        if (
            $signedDteBody === null
            || trim($signedDteBody) === ''
        ) {
            throw InvalidSignatureXmlException::because(
                'No fue posible preparar el DTE firmado para incorporarlo al EnvioDTE.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Localizar el marcador
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
            throw InvalidSignatureXmlException::because(
                'No se encontró el marcador del DTE firmado dentro del EnvioDTE.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Insertar textualmente el DTE firmado
        |--------------------------------------------------------------------------
        |
        | Aquí NO usamos importNode().
        |
        | De esta manera el XML firmado conserva exactamente sus namespaces
        | y su contenido serializado.
        |
        */

        $xml = str_replace(
            $marker,
            $signedDteBody,
            $xml
        );


        /*
        |--------------------------------------------------------------------------
        | Verificar que la firma interna continúe válida
        |--------------------------------------------------------------------------
        |
        | En este momento todavía NO existe la firma externa de SetDTE.
        | Solamente comprobamos que la firma del Documento haya sobrevivido
        | a su incorporación dentro del EnvioDTE.
        |
        */


        /*
        * En este punto el sobre todavía no tiene
        * la firma de SetDTE.
        *
        * Estamos comprobando que la firma interna
        * del DTE siga siendo válida DESPUÉS de importarlo.
        */
        $this->integrityService
            ->assertAllSignaturesValid(
                $xml
            );

        return [
            'set_dte_id' =>
                $setDteId,

            'envelope_xml' =>
                $xml,
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
