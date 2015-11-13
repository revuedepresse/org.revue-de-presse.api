<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Entity\Awareness;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
trait CreationAware
{
    /**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
