<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Security;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
interface CryptoInterface
{
    /**
     * @param $message
     * @return mixed
     */
    public function encrypt($message);
}
