<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use WeavingTheWeb\Bundle\ApiBundle\Entity\JobInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class AbstractJobAwareEvent extends Event implements JobAwareEventInterface
{
    /**
     * @var JobInterface
     */
    public $job;

    /**
     * @param JobInterface $job
     * @return $this
     */
    public function setJob(JobInterface $job)
    {
        $this->job = $job;

        return $this;
    }

    public function getJob()
    {
        return $this->job;
    }
}
