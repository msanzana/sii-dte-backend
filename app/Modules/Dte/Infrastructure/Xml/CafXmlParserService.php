<?php
namespace App\Modules\Dte\Infrastructure\Xml;

use App\Modules\Dte\Domain\Exceptions\InvalidCafException;
use DOMDocument;
use DOMElement;
use DOMXPath;

class CafXmlParserService
{
    public function parse(string $xml): array
    {
        $dom = new DOMDocument();
        $loaded = @$dom->loadXML($xml);

        if(!$loaded){
            throw InvalidCafException::because(
                'El Archivo CAF no contiene un xml valido.'
            );
        }

        $xpath = new DOMXPath($dom);
        $dteType = $this->getRequiredNodeValue(
            $xpath,
             "//*[local-name()='AUTORIZACION']/*[local-name()='CAF']/*[local-name()='DA']/*[local-name()='TD']"
        );
        $folioStart = $this->getRequiredNodeValue(
            $xpath,
            "//*[local-name()='AUTORIZACION']/*[local-name()='CAF']/*[local-name()='DA']/*[local-name()='RNG']/*[local-name()='D']"
        );

        $folioEnd = $this->getRequiredNodeValue(
            $xpath,
            "//*[local-name()='AUTORIZACION']/*[local-name()='CAF']/*[local-name()='DA']/*[local-name()='RNG']/*[local-name()='H']"
        );

        $authorizedAt = $this->getOptionalNodeValue(
            $xpath,
            "//*[local-name()='AUTORIZACION']/*[local-name()='CAF']/*[local-name()='DA']/*[local-name()='FA']"
        );

        $privateKeyNode = $this->getRequiredNode(
            $xpath,
            "//*[local-name()='AUTORIZACION']/*[local-name()='RSASK']"
        );

        $publicKeyNode = $this->getOptionalNode(
            $xpath,
            "//*[local-name()='AUTORIZACION']/*[local-name()='CAF']/*[local-name()='DA']/*[local-name()='RSAPK']"
        );

        return [
            'dte_type' => (int) $dteType,
            'folio_start' => (int) $folioStart,
            'folio_end' => (int) $folioEnd,
            'authorized_at' => $this->normalizeDate($authorizedAt),
            'private_key_material' => $privateKeyNode->C14N(),
            'public_key_material' => $publicKeyNode ? $publicKeyNode->C14N() : null,
        ];

    }
     private function getRequiredNodeValue(DOMXPath $xpath, string $expression): string
    {
        $node = $this->getRequiredNode($xpath, $expression);

        $value = trim($node->textContent);

        if ($value === '') {
            throw InvalidCafException::because(
                "El nodo requerido existe pero viene vacío: {$expression}"
            );
        }

        return $value;
    }

    private function getOptionalNodeValue(DOMXPath $xpath, string $expression): ?string
    {
        $node = $this->getOptionalNode($xpath, $expression);

        if (!$node) {
            return null;
        }

        $value = trim($node->textContent);

        return $value === '' ? null : $value;
    }

    private function getRequiredNode(DOMXPath $xpath, string $expression): DOMElement
    {
        $nodeList = $xpath->query($expression);

        if ($nodeList === false || $nodeList->length === 0) {
            throw InvalidCafException::because(
                "No se encontró el nodo requerido del CAF: {$expression}"
            );
        }

        $node = $nodeList->item(0);

        if (!$node instanceof DOMElement) {
            throw InvalidCafException::because(
                "El nodo encontrado no es un elemento XML válido: {$expression}"
            );
        }

        return $node;
    }

    private function getOptionalNode(DOMXPath $xpath, string $expression): ?DOMElement
    {
        $nodeList = $xpath->query($expression);

        if ($nodeList === false || $nodeList->length === 0) {
            return null;
        }

        $node = $nodeList->item(0);

        return $node instanceof DOMElement ? $node : null;
    }

    private function normalizeDate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = trim($value);

        if (preg_match('/^\d{8}$/', $clean) === 1) {
            return substr($clean, 0, 4) . '-' . substr($clean, 4, 2) . '-' . substr($clean, 6, 2);
        }

        return $clean;
    }
}
