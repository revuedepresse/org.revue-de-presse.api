<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Symfony\Component\HttpFoundation\JsonResponse;

use WeavingTheWeb\Bundle\ApiBundle\Entity\JobInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @Extra\Route("/job", service="weaving_the_web_api.controller.job")
 */
class JobController
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\JobRepository
     */
    public $jobRepository;

    /**
     * @var \Symfony\Component\Translation\Translator
     */
    public $translator;

    /**
     * @Extra\Route(
     *      "/{job}/output",
     *      name="weaving_the_web_api_get_job_output",
     *      requirements={"job": "\d+"},
     *      options={"expose"=true}
     * )
     * @Extra\Method({"GET"})
     * @Extra\ParamConverter(
     *      "job",
     *      class="WeavingTheWebApiBundle:Job"
     * )
     *
     * @param JobInterface $job
     * @return JsonResponse
     */
    public function getOutputAction(JobInterface $job)
    {
        /**
         * @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Job $job
         */
        if ($job->isStarted()) {
            $content = [
                'result' => $this->getOutputMessage($job, 'started'),
                'type' => 'info'
            ]
            ;
        } elseif ($job->hasFailed()) {
            $content = [
                'result' => $this->getOutputMessage($job, 'failed'),
                'type' => 'error'
            ];
        } elseif ($job->hasFinished()) {
            $content = [
                'result' => $this->getOutputMessage($job, 'finished'),
                'data' => ['url' => $job->getOutput()],
                'type' => 'success'
            ];
        } else {
            $content = [
                'result' => $this->getOutputMessage($job, 'idle'),
                'type' => 'info'
            ];
        }

        return new JsonResponse($content);
    }

    /**
     * @param JobInterface $job
     * @param $status
     * @return string
     */
    protected function getOutputMessage(JobInterface $job, $status)
    {
        return $this->translator->trans('job.output.' . $status, ['{{ job_id }}' => $job->getId()], 'job');
    }
}
