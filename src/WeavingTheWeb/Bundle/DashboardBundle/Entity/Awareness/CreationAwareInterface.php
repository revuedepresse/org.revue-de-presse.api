<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Entity\Awareness;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
interface CreationAwareInterface
{
    public function getCreatedAt();
}
