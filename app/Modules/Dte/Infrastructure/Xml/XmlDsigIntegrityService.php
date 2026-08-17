<?php

namespace App\Modules\Dte\Infrastructure\Xml;

use App\Modules\Dte\Domain\Exceptions\InvalidSignatureXmlException;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

final class XmlDsigIntegrityService
{
    private const XMLDSIG_NS =
        'http://www.w3.org/2000/09/xmldsig#';

    public function assertAllSignaturesValid(
        string $xml
    ): void {
        $dom = new DOMDocument();

        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;

        if (
            !@$dom->loadXML(
                $xml,
                LIBXML_NONET
            )
        ) {
            throw InvalidSignatureXmlException::because(
                'No fue posible cargar el XML para verificar sus firmas.'
            );
        }

        $xpath = new DOMXPath(
            $dom
        );

        $signatures = $xpath->query(
            "//*[local-name()='Signature' "
            . "and namespace-uri()='"
            . self::XMLDSIG_NS
            . "']"
        );

        if (
            $signatures === false
            || $signatures->length === 0
        ) {
            throw InvalidSignatureXmlException::because(
                'El XML no contiene firmas XMLDSig para verificar.'
            );
        }

        foreach ($signatures as $signatureNode) {
            if (
                !$signatureNode
                instanceof DOMElement
            ) {
                continue;
            }

            $this->verifySignature(
                $xpath,
                $signatureNode
            );
        }
    }

    private function verifySignature(
        DOMXPath $xpath,
        DOMElement $signatureNode
    ): void {
        /*
         * ==================================================
         * SignedInfo
         * ==================================================
         */

        $signedInfoNode =
            $this->firstElement(
                $xpath,
                "./*[local-name()='SignedInfo']",
                $signatureNode,
                'SignedInfo'
            );

        /*
         * ==================================================
         * Reference
         * ==================================================
         */

        $referenceNode =
            $this->firstElement(
                $xpath,
                "./*[local-name()='SignedInfo']"
                . "/*[local-name()='Reference']",
                $signatureNode,
                'Reference'
            );

        $uri =
            trim(
                $referenceNode
                    ->getAttribute('URI')
            );

        if (
            $uri === ''
            || !str_starts_with(
                $uri,
                '#'
            )
        ) {
            throw InvalidSignatureXmlException::because(
                "La firma contiene un Reference URI no soportado: {$uri}"
            );
        }

        $targetId =
            substr(
                $uri,
                1
            );

        /*
         * ==================================================
         * Encontrar elemento por ID
         * ==================================================
         */

        $targetNode =
            $this->findElementById(
                $xpath,
                $targetId
            );

        /*
         * ==================================================
         * DigestValue guardado
         * ==================================================
         */

        $digestValueNode =
            $this->firstElement(
                $xpath,
                "./*[local-name()='SignedInfo']"
                . "/*[local-name()='Reference']"
                . "/*[local-name()='DigestValue']",
                $signatureNode,
                'DigestValue'
            );

        $expectedDigest =
            trim(
                $digestValueNode
                    ->textContent
            );

        /*
         * ==================================================
         * Recalcular digest DESDE EL XML FINAL
         * ==================================================
         */

        $canonicalTarget =
            $targetNode->C14N(
                false,
                false
            );

        if (
            $canonicalTarget === false
            || $canonicalTarget === ''
        ) {
            throw InvalidSignatureXmlException::because(
                "No fue posible canonicalizar el nodo {$targetId}."
            );
        }

        $actualDigest =
            base64_encode(
                sha1(
                    $canonicalTarget,
                    true
                )
            );

        if (
            !hash_equals(
                $expectedDigest,
                $actualDigest
            )
        ) {
            throw InvalidSignatureXmlException::because(
                'DigestValue inválido para '
                . $targetId
                . '. Firmado: '
                . $expectedDigest
                . '. Recalculado: '
                . $actualDigest
            );
        }

        /*
         * ==================================================
         * SignatureValue
         * ==================================================
         */

        $signatureValueNode =
            $this->firstElement(
                $xpath,
                "./*[local-name()='SignatureValue']",
                $signatureNode,
                'SignatureValue'
            );

        $signatureValue =
            base64_decode(
                preg_replace(
                    '/\s+/',
                    '',
                    $signatureValueNode
                        ->textContent
                ),
                true
            );

        if ($signatureValue === false) {
            throw InvalidSignatureXmlException::because(
                'SignatureValue no contiene Base64 válido.'
            );
        }

        /*
         * ==================================================
         * Certificado X509
         * ==================================================
         */

        $certificateNode =
            $this->firstElement(
                $xpath,
                "./*[local-name()='KeyInfo']"
                . "/*[local-name()='X509Data']"
                . "/*[local-name()='X509Certificate']",
                $signatureNode,
                'X509Certificate'
            );

        $certificateBase64 =
            preg_replace(
                '/\s+/',
                '',
                $certificateNode
                    ->textContent
            );

        if (
            $certificateBase64 === null
            || $certificateBase64 === ''
        ) {
            throw InvalidSignatureXmlException::because(
                'El certificado X509 de la firma está vacío.'
            );
        }

        $certificatePem =
            "-----BEGIN CERTIFICATE-----\n"
            . chunk_split(
                $certificateBase64,
                64,
                "\n"
            )
            . "-----END CERTIFICATE-----\n";

        $publicKey =
            openssl_pkey_get_public(
                $certificatePem
            );

        if ($publicKey === false) {
            throw InvalidSignatureXmlException::because(
                'No fue posible obtener la llave pública del certificado XMLDSig.'
            );
        }

        /*
         * ==================================================
         * Canonicalizar SignedInfo FINAL
         * ==================================================
         */

        $canonicalSignedInfo =
            $signedInfoNode->C14N(
                false,
                false
            );

        if (
            $canonicalSignedInfo === false
            || $canonicalSignedInfo === ''
        ) {
            throw InvalidSignatureXmlException::because(
                'No fue posible canonicalizar SignedInfo durante la verificación.'
            );
        }

        /*
         * ==================================================
         * Verificación RSA-SHA1
         * ==================================================
         */

        $verification =
            openssl_verify(
                $canonicalSignedInfo,
                $signatureValue,
                $publicKey,
                OPENSSL_ALGO_SHA1
            );

        if ($verification !== 1) {
            throw InvalidSignatureXmlException::because(
                "SignatureValue inválido para la referencia {$uri}."
            );
        }
    }

    private function findElementById(
        DOMXPath $xpath,
        string $id
    ): DOMElement {
        $elements =
            $xpath->query(
                '//*[@ID]'
            );

        if ($elements !== false) {
            foreach ($elements as $element) {
                if (
                    $element instanceof DOMElement
                    && $element->getAttribute('ID')
                        === $id
                ) {
                    return $element;
                }
            }
        }

        throw InvalidSignatureXmlException::because(
            "No se encontró el elemento referenciado por #{$id}."
        );
    }

    private function firstElement(
        DOMXPath $xpath,
        string $expression,
        DOMNode $context,
        string $name
    ): DOMElement {
        $nodes =
            $xpath->query(
                $expression,
                $context
            );

        $node =
            $nodes !== false
                ? $nodes->item(0)
                : null;

        if (
            !$node instanceof DOMElement
        ) {
            throw InvalidSignatureXmlException::because(
                "No se encontró {$name} durante la validación XMLDSig."
            );
        }

        return $node;
    }
}
