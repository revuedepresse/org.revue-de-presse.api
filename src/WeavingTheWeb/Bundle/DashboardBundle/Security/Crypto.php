<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Security;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class Crypto implements CryptoInterface
{
    /**
     * @var \phpseclib\Crypt\AES
     */
    public $cipher;

    public function __construct($aes, $key, $iv)
    {
        $this->cipher = new $aes();

        $this->cipher->setKey(hex2bin($key));
        $this->cipher->setIV(hex2bin($iv));
    }

    public function encrypt($message)
    {
        if (isset($manualPadding)) {
            $this->cipher->disablePadding();

            $blockSize = 32;
            $paddedSubject = $message;
            $padSize = $blockSize - (strlen($paddedSubject) % $blockSize);
            $paddedSubject = $paddedSubject . str_repeat(chr($padSize), $padSize);

            $missingCharacters = strlen($paddedSubject) % 16;
            if ($missingCharacters > 0) {
                $padding = str_repeat('0', 16 - $missingCharacters - 1) . '-';
                $paddedSubject = $padding . $paddedSubject;
                $paddingLength = strlen($padding);
            } else {
                $paddingLength = 0;
            }
        } else {
            $paddingLength = 0;
            $paddedSubject = $message;
        }

        $encryptedRecords = $this->cipher->encrypt($paddedSubject);

        return [
            'padding_length' => $paddingLength,
            'encrypted_message' => base64_encode($encryptedRecords)
        ];
    }

    public function decrypt($encryptedMessage)
    {
        return $this->cipher->decrypt(base64_decode($encryptedMessage));
    }
}
