<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Security\Core\User\UserInterface;

use WeavingTheWeb\Bundle\ApiBundle\Entity\Job,
    WeavingTheWeb\Bundle\ApiBundle\Entity\JobInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class JobRepository extends EntityRepository
{
    /**
     * @var \Symfony\Component\Translation\Translator
     */
    public $translator;

    /**
     * @param $command
     * @param UserInterface $user
     * @return Job
     */
    public function makeCommandJob($command, UserInterface $user)
    {
        $job = new Job(JobInterface::STATUS_IDLE, JobInterface::TYPE_COMMAND);
        $job->setValue($command);
        $job->setUser($user);

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

    /**
     * @param UserInterface $user
     * @param array $orderBy
     * @param int $limit
     * @return array
     */
    public function findJobsBy(UserInterface $user, array $orderBy, $limit = 10)
    {
        $sortingColumns = array_keys($orderBy);
        $alias = 'j';

        /**
         * @var \Doctrine\ORM\QueryBuilder $queryBuilder
         */
        $queryBuilder = $this->createQueryBuilder($alias);
        $queryBuilder = $queryBuilder->select('j.id as Id')
            ->addSelect('j.id')
            ->addSelect('j.status as Status')
            ->addSelect('j.createdAt as Date')
            ->andWhere('j.user = :user');

        foreach ($sortingColumns as $column) {
            $queryBuilder->orderBy($alias . '.' . $column, $orderBy[$column]);
        }

        $queryBuilder->setParameter('user', $user);
        $queryBuilder->setMaxResults($limit);

        $results = $queryBuilder->getQuery()->getArrayResult();
        array_walk($results, function (&$job) {
            $job['Date'] = $job['Date']->format('Y-m-d H:i');
            $job['Status'] = $this->getTranslatedStatusMessage(new Job($job['Status']));
            $job['entity'] = 'job';
        });

        return $results;
    }

    /**
     * @param JobInterface $job
     * @return array
     */
    public function getOutputResponseContent(JobInterface $job)
    {
        $content['result'] = $this->getTranslatedStatusMessage($job);

        /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Job $job */
        if ($job->hasFailed()) {
            $content['type'] = 'error';
        } elseif ($job->hasFinished()) {
            $content['data'] = ['url' => $job->getOutput()];
            $content['type'] = 'success';
        } else {
            $content['type'] = 'info';
        }

        return $content;
    }

    /**
     * @param JobInterface $job
     * @return string
     */
    public function getTranslatedStatusMessage(JobInterface $job)
    {
        $statusLabel = $this->getJobStatusLabel($job);

        return $this->translator->trans('job.output.' . $statusLabel, [], 'job');
    }

    /**
     * @param JobInterface $job
     * @return string
     */
    protected function getJobStatusLabel(JobInterface $job)
    {
        /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Job $job */
        $status = $job->getStatus();
        $jobReflection = new \ReflectionClass($job);
        $constants = $jobReflection->getConstants();
        $statuses = array_flip($constants);
        $statusConstantName = $statuses[$status];

        return strtolower(str_replace(Job::PREFIX_STATUS, '', $statusConstantName));
    }
}
