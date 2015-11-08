<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Command;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\ArrayInput;

use WeavingTheWeb\Bundle\ApiBundle\Entity\Job,
    WeavingTheWeb\Bundle\ApiBundle\Entity\JobInterface;

use WeavingTheWeb\Bundle\ApiBundle\Exception\UnauthorizedCommandException,
    WeavingTheWeb\Bundle\ApiBundle\Exception\UnavailableCommandException;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class RunJobCommand extends Command
{
    public $authorizedCommands = [
        'weaving-the-web:perspective:export'
    ];

    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\JobRepository
     */
    public $repository;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    public $entityManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @var \Symfony\Component\Translation\Translator
     */
    public $translator;

    /**
     * @var OutputInterface
     */
    private $output;

    public function configure()
    {
        parent::configure();

        $this->setName('weaving-the-web:job:run')
            ->setDescription('Run all the jobs available')
            ->setAliases(['wtw:job:run']);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|mixed
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        try {
            $jobs = $this->repository->findIdleCommandJobs();
            $this->runJobs($jobs);
            $outputMessage = $this->translator->trans('job.run.success', [], 'job');
            $returnCode = 0;
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $returnCode = $exception->getCode();
            $outputMessage = $this->translator->trans('job.run.error', [], 'job');
        }

        $output->writeln($outputMessage);


        return $returnCode;
    }

    /**
     * @param $jobs
     * @throws UnauthorizedCommandException
     */
    protected function runJobs($jobs)
    {
        foreach ($jobs as $job) {
            /**
             * @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Job $job
             */
            $command = $job->getValue();
            
            try {
                $this->validateCommand($command);

                $job->setStatus(JobInterface::STATUS_STARTED);
                $this->updateJob($job);

                $command = $this->getApplication()->get($command);
                $success = $command->run(
                    new ArrayInput([
                        'command' => $command,
                        '--job' => $job->getId()
                    ]), $this->output
                );

                if (intval($success) === 0) {
                    $job->setStatus(JobInterface::STATUS_FINISHED);
                } else {
                    $job->setStatus(JobInterface::STATUS_FAILED);
                }

                $this->updateJob($job);
            } catch (UnauthorizedCommandException $exception) {
                $message = $this->translator->trans('job.run.unauthorized', [
                    '{{ command }}' => $command
                ], 'job');
                $this->logger->error($exception->getMessage());
                $this->output->writeln($message);

                $job->setStatus(JobInterface::STATUS_FAILED);
                $this->updateJob($job);
                continue;
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage());
                continue;
            }
        }
    }

    /**
     * @param $command
     * @throws UnauthorizedCommandException
     * @throws UnavailableCommandException
     */
    protected function validateCommand($command)
    {
        if (!$this->getApplication()->has($command)) {
            throw new UnavailableCommandException(
                sprintf(
                    'The command "%s" cant not be found',
                    $command
                )
            );
        }

        $authorizedCommands = array_flip($this->authorizedCommands);
        if (!array_key_exists($command, $authorizedCommands)) {
            throw new UnauthorizedCommandException(
                sprintf(
                    'The command "%s" is not authorized to run as a job',
                    $command
                )
            );
        }
    }

    /**
     * @param $job
     */
    protected function updateJob(Job $job)
    {
        $job->setUpdatedAt(new \DateTime());
        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }
}
