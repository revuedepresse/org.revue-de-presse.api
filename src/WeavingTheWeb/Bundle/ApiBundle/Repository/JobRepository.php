<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;

use WeavingTheWeb\Bundle\ApiBundle\Entity\Job,
    WeavingTheWeb\Bundle\ApiBundle\Entity\JobInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class JobRepository extends EntityRepository
{
    public function makeCommandJob($command)
    {
        $job = new Job(JobInterface::STATUS_IDLE, JobInterface::TYPE_COMMAND);
        $job->setValue($command);

        return $job;
    }

    /**
     * @param $command
     * @return bool
     */
    public function idleJobExistsForCommand($command)
    {
        $pendingJob = $this->findOneBy([
            'value' => $command,
            'status' => Job::STATUS_IDLE
        ]);

        return !is_null($pendingJob);
    }

    /**
     * @return array
     */
    public function findIdleCommandJobs()
    {
        return $this->findBy(
            [
                'type' => Job::TYPE_COMMAND,
                'status' => Job::STATUS_IDLE
            ]
        );
    }

    /**
     * @return array
     */
    public function findFinishedCommandJobs()
    {
        return $this->findBy(
            [
                'type' => Job::TYPE_COMMAND,
                'status' => Job::STATUS_FINISHED
            ]
        );
    }

    /**
     * @return array
     */
    public function findFailedCommandJobs()
    {
        return $this->findBy(
            [
                'type' => Job::TYPE_COMMAND,
                'status' => Job::STATUS_FAILED
            ]
        );
    }

    /**
     * @param $id
     * @return null|Job
     */
    public function findStartedCommandJobById($id)
    {
        return $this->findOneBy(
            [
                'id' => $id,
                'status' => Job::STATUS_STARTED
            ]
        );
    }
}
