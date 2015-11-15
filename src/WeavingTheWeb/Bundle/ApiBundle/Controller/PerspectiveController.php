<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface,
    Symfony\Component\Security\Core\User\UserInterface;

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
     * @var \WeavingTheWeb\Bundle\DashboardBundle\Command\ExportPerspectivesCommand
     */
    public $exportPerspectiveCommand;

    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\JobRepository
     */
    public $jobRepository;

    /**
     * @var \Symfony\Component\Translation\Translator
     */
    public $translator;

    /**
     * @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage
     */
    public $tokenStorage;

    /**
     * @Extra\Route(
     *      "/export",
     *      name="weaving_the_web_api_export_perspectives",
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
            $token = $this->tokenStorage->getToken();
            $job = $this->jobRepository->makeCommandJob($command, $token->getUser());

            $this->entityManager->persist($job);
            $this->entityManager->flush();

            $message = $this->translator->trans('perspective.job.submitted', [], 'perspective');

            return new JsonResponse([
                'job' => [
                    'id' => $job->getId(),
                    'status' => $job->getStatus()
                ],
                'status' => $message,
                'type' => 'success'
            ]);
        }
    }
}
