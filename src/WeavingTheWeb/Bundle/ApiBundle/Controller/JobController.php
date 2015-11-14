<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException,
    Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\BinaryFileResponse,
    Symfony\Component\HttpFoundation\ResponseHeaderBag;

use WeavingTheWeb\Bundle\ApiBundle\Entity\JobInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @Extra\Route("/api/job", service="weaving_the_web_api.controller.job")
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
     * @var string
     */
    public $archiveDir;

    /**
     * @Extra\Route(
     *      "/",
     *      name="weaving_the_web_api_get_jobs",
     *      options={"expose"=true}
     * )
     * @Extra\Method({"GET"})
     *
     * @return JsonResponse
     */
    public function getJobsActions()
    {
        try {
            // TODO Find job on a per-user basis
            $jobs = $this->jobRepository->findBy([], ['createdAt' => 'DESC'], 10);
            $type = 'success';
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $jobs = [];
            $type = 'error';
        }

        return new JsonResponse([
            'collection' => $jobs,
            'type' => $type,
        ]);
    }

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

    /**
     * @Extra\Route(
     *      "/archive/{filename}.zip",
     *      name="weaving_the_web_api_get_archive",
     *      requirements={"filename": "[-a-zA-Z0-9]{36,36}"},
     *      options={"expose"= true}
     * )
     * @Extra\Method({"GET"})
     *
     * @param $filename
     * @return BinaryFileResponse
     */
    public function getArchiveAction($filename)
    {
        $filenameWithExtension = $filename . '.zip';
        $archivePath = realpath($this->archiveDir) . '/' . $filenameWithExtension;
        if (!file_exists($archivePath)) {
            throw new NotFoundHttpException();
        }

        $response = new BinaryFileResponse($archivePath, 200, [
            'Content-Type' => 'application/zip, application/octet-stream'
        ]);

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filenameWithExtension
        );
        $response->headers->set('Content-Length', filesize($archivePath));

        return $response;
    }
}
