<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Entity\Awareness;

use Doctrine\ORM\Mapping as ORM;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
trait SelectionAware
{
    /**
     * @return mixed
     */
    public function isSelected()
    {
        return $this->selected;
    }

    /**
     * @var bool
     *
     * @ORM\Column(name="selected", type="boolean", options={"default": false})
     */
    private $selected = false;

    /**
     * @param $selected
     * @return $this
     */
    public function setSelected($selected) {
        $this->selected = $selected;
        if ($this->selected) {
            $this->selectedAt = new \DateTime();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function select()
    {
        $this->setSelected(true);

        return $this;
    }

    /**
     * @return $this
     */
    public function unselect()
    {
        $this->setSelected(false);

        return $this;
    }

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="selected_at", type="datetime", nullable=true)
     */
    private $selectedAt;

    /**
     * @return mixed
     */
    public function getSelectedAt()
    {
        return $this->selectedAt;
    }

    /**
     * @param mixed $selectedAt
     * @return $this
     */
    public function setSelectedAt(\DateTime $selectedAt)
    {
        $this->selectedAt = $selectedAt;

        return $this;
    }
}
