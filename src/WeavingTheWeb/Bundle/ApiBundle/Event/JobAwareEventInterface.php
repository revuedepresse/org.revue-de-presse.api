<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Event;

use WeavingTheWeb\Bundle\ApiBundle\Entity\JobInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
interface JobAwareEventInterface
{
    /**
     * @return JobInterface
     */
    public function getJob();

    /**
     * @param JobInterface $job
     * @return mixed
     */
    public function setJob(JobInterface $job);
}
