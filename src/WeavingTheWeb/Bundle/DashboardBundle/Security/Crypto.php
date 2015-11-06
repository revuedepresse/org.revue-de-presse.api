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

    private $key;

    private $iv;

    public function __construct($aes, $key, $iv)
    {
        $this->cipher = new $aes();
        $this->key = $key;
        $this->iv = $iv;
    }

    public function encrypt($message)
    {
        $key = $this->key;
        $iv = $this->iv;

        $this->cipher->setKey(hex2bin($key));
        $this->cipher->setIV(hex2bin($iv));

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
}
