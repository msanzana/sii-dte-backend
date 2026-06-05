<?php
namespace App\Modules\Dte\Infrastructure\Xml;

use App\Modules\Dte\Domain\Exceptions\InvalidSignatureXmlException;
use DOMDocument;
use DOMElement;
use DOMXPath;

class EnvioDteSignatureService
{
    private const XMLDSIG_NS = 'http://www.w3.org/2000/09/xmldsig#';
    private const C14N_ALGORITHM = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    private const SIGNATURE_ALGORITHM = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';
    private const DIGEST_ALGORITHM = 'http://www.w3.org/2000/09/xmldsig#sha1';

    public function SignSetDte(
        string $envioXml,
        string $privateKeyPem,
        string $certificateBase64,
        string $modulosBase64,
        string $exponentBase64
    ):string
    {
        $dom = new DOMDocument('1.0','ISO-8859-1');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $loaded = @$dom->loadXML($envioXml);

        if (!$loaded)
        {
            throw InvalidSignatureXmlException::because(
                'No fue posible cargar el EnvioDTE para firmar el SetDTE.'
            );
        }

        $xpath = new DOMXPath($dom);

        $envioNode = $xpath->query("/*[local-name()='EnvioDTE']")->item(0);
        $setDteNode = $xpath->query("/*[local-name()='EnvioDte']/*[local-name()='SetDte']")->item(0);

        if(!$envioNode instanceof DOMElement || !$setDteNode instanceof DOMElement)
        {
            throw InvalidSignatureXmlException::because(
                'No se encontró EnvioDTE o SetDTE en el XML del envío'
            );
        }

        if (!$setDteNode->hasAttribute('ID'))
        {
            throw InvalidSignatureXmlException::because(
                'SetDTE no tiene atributo ID.'
            );
        }

        $setDteId= trim($setDteNode->getAttribute('ID'));

        if($setDteId === '')
        {
            throw InvalidSignatureXmlException::because(
                'El atributo ID de SetDTE está vacío.'
            );
        }

        $canonicalSetDte = $setDteNode->C14N(false,false);

        if($canonicalSetDte === false || $canonicalSetDte === '')
        {
            throw InvalidSignatureXmlException::because(
                'No fue posible canonicalizar SetDTE.'
            );
        }

        $digestValue = base64_encode(sha1($canonicalSetDte,true));

        $signatureNode = $dom->createElementNS(self::XMLDSIG_NS,'Signature');
        $signedInfoNode = $dom->createElementNS(self::XMLDSIG_NS,'SignedInfo');

        $canonicalizationMethodNode = $dom->createElementNS(self::XMLDSIG_NS, 'CanonicalizationMethod');
        $canonicalizationMethodNode->setAttribute('Algorithm', self::C14N_ALGORITHM);

        $signatureMethodNode = $dom->createElementNS(self::XMLDSIG_NS,'SignatureMethod');
        $signatureMethodNode->setAttribute('Algorithm', self::SIGNATURE_ALGORITHM);

        $referenceNode = $dom->createElementNS(self::XMLDSIG_NS,'Reference');
        $referenceNode->setAttribute('URI', '#'.$setDteId);

        $transformsNode = $dom->createElementNS(self::XMLDSIG_NS,'Transforms');
        $transformNode = $dom->createElementNS(self::XMLDSIG_NS,'Transform');
        $transformNode->setAttribute('Algorithm', self::C14N_ALGORITHM);
        $transformsNode->appendChild($transformNode);

        $digestMethodNode = $dom->createElementNS(self::XMLDSIG_NS,'DigestMethod');
        $digestMethodNode->setAttribute('Algorithm', self::DIGEST_ALGORITHM);

        $digestValueNode = $dom->createElementNS(self::XMLDSIG_NS, 'DigestValue', $digestValue);
        $digestValueNode->appendChild($dom->createTextNode($digestValue));

        $referenceNode->appendChild($transformsNode);
        $referenceNode->appendChild($digestMethodNode);
        $referenceNode->appendChild($digestValueNode);

        $signedInfoNode->appendChild($canonicalizationMethodNode);
        $signedInfoNode->appendChild($signatureMethodNode);
        $signedInfoNode->appendChild($referenceNode);

        $signatureNode->appendChild($signedInfoNode);

        $canonicalSignedInfo = $signedInfoNode->C14N(false,false);

        if($canonicalSignedInfo === false || $canonicalSignedInfo === '')
        {
            throw InvalidSignatureXmlException::because(
                'No fue posible canonicalizar SignedInfo de SetDTE.'
            );
        }

        $privateKey = openssl_pkey_get_private($privateKeyPem);

        if($privateKey === false)
        {
            throw InvalidSignatureXmlException::because(
                'No fue posible cargar la llave privada del certificado para firmar el SetDTE.'
            );
        }

        $rawSignature = '';
        $signed = openssl_sign(
            $canonicalSignedInfo,
            $rawSignature,
            $privateKey,
            OPENSSL_ALGO_SHA1
        );

        if(!$signed)
        {
            throw InvalidSignatureXmlException::because(
                'OpenSLL no pudo firmar SignedInfo del SetDTE.'
            );
        }

        $signatureValueNode = $dom->createElementNS(self::XMLDSIG_NS,'SignatureValue');
        $signatureValueNode->appendChild(
            $dom->createTextNode(base64_encode($rawSignature))
        );

        $signatureNode->appendChild($signatureValueNode);

        $keyInfoNode = $dom->createElementNS(self::XMLDSIG_NS,'KeyInfo');

        $keyValueNode = $dom->createElementNS(self::XMLDSIG_NS,'KeyValue');
        $rsaKeyValueNode = $dom->createElementNS(self::XMLDSIG_NS,'RSAKeyValue');

        $modulusNode = $dom->createElementNS(self::XMLDSIG_NS,'Modulus');
        $modulusNode->appendChild($dom->createTextNode($modulosBase64));

        $exponentNode = $dom->createElementNS(self::XMLDSIG_NS,'Exponent');
        $exponentNode->appendChild($dom->createTextNode($exponentBase64));

        $rsaKeyValueNode->appendChild($modulusNode);
        $rsaKeyValueNode->appendChild($exponentNode);
        $keyValueNode->appendChild($rsaKeyValueNode);

        $x509DataNode = $dom->createElementNS(self::XMLDSIG_NS,'X509Data');
        $x509CertificateNode = $dom->createElementNS(self::XMLDSIG_NS,'X509Certificate');
        $x509CertificateNode->appendChild($dom->createTextNode($certificateBase64));
        $x509DataNode->appendChild($x509CertificateNode);

        $keyInfoNode->appendChild($keyValueNode);
        $keyInfoNode->appendChild($x509DataNode);
        $signatureNode->appendChild($keyInfoNode);

        $envioNode->appendChild($signatureNode);

        $xml = $dom->saveXML();

        if ($xml === false || $xml === '')
        {
            throw InvalidSignatureXmlException::because(
                'No fue posible serializar el EnvioDte firmado.'
            );
        }

        return $xml;
    }
}
