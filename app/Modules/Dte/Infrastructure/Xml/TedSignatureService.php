<?php

namespace App\Modules\Dte\Infrastructure\Xml;

use App\Modules\Dte\Domain\Exceptions\InvalidTedDataException;

class TedSignatureService
{
    public function singDdXml(string $ddXmlUtf8, string $privateKeyPem):string
    {
        $ddXmlLatin1 = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $ddXmlUtf8);

                if ($ddXmlLatin1 === false || $ddXmlLatin1 === '') {
            throw InvalidTedDataException::because(
                'No fue posible convertir el bloque DD a ISO-8859-1 antes de firmarlo.'
            );
        }

        $privateKey = openssl_pkey_get_private($privateKeyPem);

        if ($privateKey === false) {
            throw InvalidTedDataException::because(
                'No fue posible cargar la llave privada del CAF para firmar el TED.'
            );
        }

        $signature = '';

        $ok = openssl_sign(
            $ddXmlLatin1,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA1
        );

        openssl_free_key($privateKey);

        if (!$ok) {
            throw InvalidTedDataException::because(
                'OpenSSL no pudo firmar el bloque DD del TED.'
            );
        }

        return base64_encode($signature);
    }
}
