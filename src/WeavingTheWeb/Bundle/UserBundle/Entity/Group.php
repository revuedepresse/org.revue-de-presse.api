<?php

namespace WeavingTheWeb\Bundle\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="weaving_group")
 */
class Group
{
    /**
     * @ORM\Id
     * @ORM\Column(name="rol_id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
     protected $id;
}
