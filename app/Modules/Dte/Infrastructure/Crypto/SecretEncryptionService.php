<?php
namespace App\Modules\Dte\Infrastructure\Crypto;

use Illuminate\Support\Facades\Crypt;

class SecretEncryptionService
{
    public function encrypt(string $plainText):string
    {
        return Crypt::encryptString($plainText);
    }
    public function decrypt(string $cipherText):string
    {
        return Crypt::decryptString($cipherText);
    }
}
