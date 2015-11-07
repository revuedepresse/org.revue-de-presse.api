<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 *
 * @Extra\Route(
 *      "/api/perspective",
 *      service="weaving_the_web_api.controller.perspective"
 * )
 */
class PerspectiveController
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    public $entityManager;

    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\JobRepository
     */
    public $jobRepository;

    /**
     * @var \WeavingTheWeb\Bundle\DashboardBundle\Command\ExportPerspectivesCommand
     */
    public $exportPerspectiveCommand;

    /**
     * @var \Symfony\Component\Translation\Translator
     */
    public $translator;

    /**
     * @Extra\Route(
     *      "/export",
     *      name="weaving_the_web_api_perspective_export",
     *      options={"expose"=true}
     * )
     * @Extra\Method({"POST"})
     */
    public function exportAction()
    {
        $command = $this->exportPerspectiveCommand->getName();
        if ($this->jobRepository->idleJobExistsForCommand($command)) {
            $message = $this->translator->trans('perspective.job.existing', [], 'perspective');

            return new JsonResponse([
                'result' => $message,
                'type' => 'error'
            ], 429);
        } else {
            $job = $this->jobRepository->makeCommandJob($command);
            $this->entityManager->persist($job);
            $this->entityManager->flush();

            $message = $this->translator->trans('perspective.job.submitted', [], 'perspective');

            return new JsonResponse([
                'result' => $message,
                'type' => 'success'
            ]);
        }
    }
}
