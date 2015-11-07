<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Security;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
interface CryptoInterface
{
    /**
     * @param $message
     * @param null $name
     * @return mixed
     */
    public function encrypt($message, $name = null);

    /**
     * @param $encryptedMessage
     * @return mixed
     */
    public function decrypt($encryptedMessage);
}
