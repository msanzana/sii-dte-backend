<?php

namespace App\Modules\Dte\Infrastructure\Sii;

use App\Modules\Dte\Domain\Exceptions\SiiAuthenticationException;
use DOMDocument;

final class SiiSeedXmlSignerService
{
    private const XMLDSIG_NS =
        'http://www.w3.org/2000/09/xmldsig#';

    private const C14N_ALGORITHM =
        'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';

    private const SIGNATURE_ALGORITHM =
        'http://www.w3.org/2000/09/xmldsig#rsa-sha1';

    private const DIGEST_ALGORITHM =
        'http://www.w3.org/2000/09/xmldsig#sha1';

    private const ENVELOPED_SIGNATURE_ALGORITHM =
        'http://www.w3.org/2000/09/xmldsig#enveloped-signature';

    public function sign(
        string $seed,
        string $privateKeyPem,
        string $certificateBase64,
        string $modulusBase64,
        string $exponentBase64,
    ): string {
        $certificateBase64 =
            $this->normalizeBase64(
                $certificateBase64
            );

        $modulusBase64 =
            $this->normalizeBase64(
                $modulusBase64
            );

        $exponentBase64 =
            $this->normalizeBase64(
                $exponentBase64
            );

        if ($certificateBase64 === '') {
            throw SiiAuthenticationException::because(
                'El certificado X509 para autenticación SII está vacío.'
            );
        }

        if ($modulusBase64 === '') {
            throw SiiAuthenticationException::because(
                'El módulo RSA para autenticación SII está vacío.'
            );
        }

        if ($exponentBase64 === '') {
            throw SiiAuthenticationException::because(
                'El exponente RSA para autenticación SII está vacío.'
            );
        }

        /*
         * ======================================================
         * XML BASE
         * ======================================================
         *
         * <getToken>
         *     <item>
         *         <Semilla>...</Semilla>
         *     </item>
         * </getToken>
         */

        $dom = new DOMDocument(
            '1.0',
            'UTF-8'
        );

        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;

        $getTokenNode =
            $dom->createElement(
                'getToken'
            );

        $dom->appendChild(
            $getTokenNode
        );

        $itemNode =
            $dom->createElement(
                'item'
            );

        $getTokenNode->appendChild(
            $itemNode
        );

        $seedNode =
            $dom->createElement(
                'Semilla'
            );

        $seedNode->appendChild(
            $dom->createTextNode(
                trim($seed)
            )
        );

        $itemNode->appendChild(
            $seedNode
        );

        /*
         * ======================================================
         * DIGEST DEL DOCUMENTO
         * ======================================================
         *
         * URI="" referencia al documento actual.
         *
         * Signature todavía no existe en el DOM, por lo que
         * canonicalizar getToken en este punto equivale al
         * resultado de aplicar enveloped-signature.
         */

        $canonicalGetToken =
            $getTokenNode->C14N(
                false,
                false
            );

        if (
            $canonicalGetToken === false
            || $canonicalGetToken === ''
        ) {
            throw SiiAuthenticationException::because(
                'No fue posible canonicalizar getToken para calcular DigestValue.'
            );
        }

        $digestValue =
            base64_encode(
                sha1(
                    $canonicalGetToken,
                    true
                )
            );

        /*
         * ======================================================
         * SIGNATURE
         * ======================================================
         */

        $signatureNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'Signature'
            );

        /*
         * ======================================================
         * SIGNED INFO
         * ======================================================
         */

        $signedInfoNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'SignedInfo'
            );

        $canonicalizationMethodNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'CanonicalizationMethod'
            );

        $canonicalizationMethodNode->setAttribute(
            'Algorithm',
            self::C14N_ALGORITHM
        );

        $signatureMethodNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'SignatureMethod'
            );

        $signatureMethodNode->setAttribute(
            'Algorithm',
            self::SIGNATURE_ALGORITHM
        );

        /*
         * ======================================================
         * REFERENCE
         * ======================================================
         */

        $referenceNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'Reference'
            );

        $referenceNode->setAttribute(
            'URI',
            ''
        );

        /*
         * Transforms
         */

        $transformsNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'Transforms'
            );

        $transformNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'Transform'
            );

        $transformNode->setAttribute(
            'Algorithm',
            self::ENVELOPED_SIGNATURE_ALGORITHM
        );

        $transformsNode->appendChild(
            $transformNode
        );

        /*
         * DigestMethod
         */

        $digestMethodNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'DigestMethod'
            );

        $digestMethodNode->setAttribute(
            'Algorithm',
            self::DIGEST_ALGORITHM
        );

        /*
         * DigestValue
         */

        $digestValueNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'DigestValue'
            );

        $digestValueNode->appendChild(
            $dom->createTextNode(
                $digestValue
            )
        );

        $referenceNode->appendChild(
            $transformsNode
        );

        $referenceNode->appendChild(
            $digestMethodNode
        );

        $referenceNode->appendChild(
            $digestValueNode
        );

        /*
         * SignedInfo completo.
         */

        $signedInfoNode->appendChild(
            $canonicalizationMethodNode
        );

        $signedInfoNode->appendChild(
            $signatureMethodNode
        );

        $signedInfoNode->appendChild(
            $referenceNode
        );

        $signatureNode->appendChild(
            $signedInfoNode
        );

        /*
         * Signature es hermano de item:
         *
         * <getToken>
         *     <item>...</item>
         *     <Signature>...</Signature>
         * </getToken>
         *
         * Debe estar conectado al DOM antes de canonicalizar
         * SignedInfo.
         */

        $getTokenNode->appendChild(
            $signatureNode
        );

        /*
         * ======================================================
         * CANONICALIZAR SIGNED INFO
         * ======================================================
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
            throw SiiAuthenticationException::because(
                'No fue posible canonicalizar SignedInfo de la semilla.'
            );
        }

        /*
         * ======================================================
         * LLAVE PRIVADA
         * ======================================================
         */

        $privateKey =
            openssl_pkey_get_private(
                $privateKeyPem
            );

        if ($privateKey === false) {
            throw SiiAuthenticationException::because(
                'No fue posible cargar la llave privada para firmar la semilla.'
            );
        }

        /*
         * ======================================================
         * FIRMAR SIGNED INFO
         * ======================================================
         */

        $rawSignature = '';

        $signed =
            openssl_sign(
                $canonicalSignedInfo,
                $rawSignature,
                $privateKey,
                OPENSSL_ALGO_SHA1
            );

        if (!$signed) {
            throw SiiAuthenticationException::because(
                'OpenSSL no pudo firmar SignedInfo de la semilla.'
            );
        }

        /*
         * ======================================================
         * SIGNATURE VALUE
         * ======================================================
         */

        $signatureValueNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'SignatureValue'
            );

        $signatureValueNode->appendChild(
            $dom->createTextNode(
                base64_encode(
                    $rawSignature
                )
            )
        );

        $signatureNode->appendChild(
            $signatureValueNode
        );

        /*
         * ======================================================
         * KEY INFO
         * ======================================================
         */

        $keyInfoNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'KeyInfo'
            );

        $keyValueNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'KeyValue'
            );

        $rsaKeyValueNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'RSAKeyValue'
            );

        /*
         * Modulus
         */

        $modulusNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'Modulus'
            );

        $modulusNode->appendChild(
            $dom->createTextNode(
                $modulusBase64
            )
        );

        /*
         * Exponent
         */

        $exponentNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'Exponent'
            );

        $exponentNode->appendChild(
            $dom->createTextNode(
                $exponentBase64
            )
        );

        $rsaKeyValueNode->appendChild(
            $modulusNode
        );

        $rsaKeyValueNode->appendChild(
            $exponentNode
        );

        $keyValueNode->appendChild(
            $rsaKeyValueNode
        );

        /*
         * X509
         */

        $x509DataNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'X509Data'
            );

        $x509CertificateNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'X509Certificate'
            );

        $x509CertificateNode->appendChild(
            $dom->createTextNode(
                $certificateBase64
            )
        );

        $x509DataNode->appendChild(
            $x509CertificateNode
        );

        $keyInfoNode->appendChild(
            $keyValueNode
        );

        $keyInfoNode->appendChild(
            $x509DataNode
        );

        $signatureNode->appendChild(
            $keyInfoNode
        );

        /*
         * ======================================================
         * SERIALIZAR
         * ======================================================
         */

        $xml =
            $dom->saveXML();

        if (
            $xml === false
            || trim($xml) === ''
        ) {
            throw SiiAuthenticationException::because(
                'No fue posible serializar la semilla firmada.'
            );
        }

        return $xml;
    }

    private function normalizeBase64(
        string $value
    ): string {
        $normalized =
            preg_replace(
                '/\s+/',
                '',
                trim($value)
            );

        return $normalized ?? '';
    }
}