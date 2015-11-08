<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Command;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
interface JobAwareCommandInterface
{
    const OPTION_JOB = 'job';

    public function getJobRepository();

    public function addJobOption();
}
