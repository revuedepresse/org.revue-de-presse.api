<?php

namespace WeavingTheWeb\Bundle\UserBundle\Entity;

use FOS\UserBundle\Model\Group as BaseGroup;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="weaving_group")
 */
class Group extends BaseGroup
{
    /**
     * @ORM\Id
     * @ORM\Column(name="rol_id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
     protected $id;
}
