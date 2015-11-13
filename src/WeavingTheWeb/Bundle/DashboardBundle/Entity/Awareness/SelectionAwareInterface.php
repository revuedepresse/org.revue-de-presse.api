<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Entity\Awareness;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
interface SelectionAwareInterface
{
    public function isSelected();

    public function select();

    public function setSelectedAt(\DateTime $selectedAt);

    public function getSelectedAt();

    public function unselect();
}
