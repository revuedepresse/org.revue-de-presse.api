<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller;

use Doctrine\Orm\NoResultException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Symfony\Component\DependencyInjection\ContainerAware,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException,
    Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\RedirectResponse;

use WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective;

use phpseclib\Crypt\AES;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 *
 * @Extra\Route("/perspective")
 */
class PerspectiveController extends ContainerAware
{
    /**
     * @Extra\Cache(expires="+2 hours", public="true")
     * @Extra\Route("/sitemap", name="weaving_the_web_dashboard_show_sitemap")
     * @Extra\Template("WeavingTheWebDashboardBundle:Perspective:showSitemap.html.twig")
     *
     * @return array
     */
    public function showSitemapAction()
    {
        /** @var \WeavingTheWeb\Bundle\DashboardBundle\Routing\Router $router */
        $router = $this->container->get('weaving_the_web_dashboard.router');

        /** @var \Symfony\Bundle\TwigBundle\TwigEngine $templateEngine */
        $templateEngine = $this->container->get('templating');
        $response = $templateEngine->renderResponse(
             'WeavingTheWebDashboardBundle:Perspective:showSitemap.html.twig', array(
                'perspectives' => $router->getPublicPerspectivesSitemap(),
                'title' => 'title_sitemap'
            )
        );
        $dateTime = new \DateTime();
        $dateInterval = \DateInterval::createFromDateString('2 hours');
        $expiresAt = $dateTime->add($dateInterval);
        $response->setExpires($expiresAt);

        return $response;
    }

    /**
     * @Extra\Route("/", name="weaving_the_web_dashboard_show_public_perspectives")
     */
    public function showPublicPerspectivesAction()
    {
        /**
         * @var \Symfony\Component\Routing\Router $router
         */
        $router = $this->container->get('router');
        $url = $router->generate('weaving_the_web_dashboard_show_sitemap');

        return new RedirectResponse($url);

    }

    /**
     * @Extra\Cache(expires="+2 hours", public="true")
     * @Extra\Route("/{hash}", name="weaving_the_web_dashboard_show_perspective")
     */
    public function showPerspectiveAction($hash)
    {
        /**
         * @var \Doctrine\Orm\EntityManager $entityManager
         */
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        /**
         * @var \WeavingTheWeb\Bundle\DashboardBundle\Repository\PerspectiveRepository $perspectiveRepository
         */
        $perspectiveRepository = $entityManager->getRepository('WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective');

        try {
            /**
             * @var \WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective $perspective
             */
            $perspective = $perspectiveRepository->findOneByPartialHash($hash);
        } catch (NoResultException $exception) {
            throw new NotFoundHttpException('The requested query it not available', $exception);
        }

        if ($this->validPerspective($perspective)) {
            $query = $this->executeQuery($perspective);

            $translator = $this->container->get('translator');

             /** @var \Symfony\Bundle\TwigBundle\TwigEngine $templateEngine */
            $templateEngine = $this->container->get('templating');

            if ($perspective->getName() !== null) {
                $perspectiveTitle = $perspective->getName();
            } else {
                $perspectiveTitle = $translator->trans('title_perspective');
            }

            $response = $templateEngine->renderResponse(
                 'WeavingTheWebDashboardBundle:Perspective:showPerspective.html.twig', array(
                     'active_menu_item' => 'dashboard',
                     'error' => $query->error,
                     'default_query' => $query->sql,
                     'records' => $query->records,
                     'title' => $perspectiveTitle
                )
            );
            $dateTime = new \DateTime();
            $dateInterval = \DateInterval::createFromDateString('2 hours');
            $expiresAt = $dateTime->add($dateInterval);
            $response->setExpires($expiresAt);

            return $response;
        } else {
            throw new NotFoundHttpException('The requested query it not available');
        }
    }

    /**
     * @param $perspective
     * @return bool
     */
    public function validPerspective($perspective)
    {
        /**
         * @var \Symfony\Component\Validator\Validator $validator
         */
        $validator = $this->container->get('validator');
        $constraintsViolationsList = $validator->validate($perspective, ['public_perspectives']);

        return count($constraintsViolationsList) === 0;
    }

    /**
     * @Extra\Route("/{hash}/export", name="weaving_the_web_dashboard_export_perspective",
     *              options={"expose"=true})
     * @Extra\Method({"GET"})
     * @Extra\ParamConverter(
     *      "perspective",
     *      class="WeavingTheWebDashboardBundle:Perspective"
     * )
     *
     * @param Perspective $perspective
     * @return NotFoundHttpException
     */
    public function exportPerspectiveAction(Perspective $perspective)
    {
        /**
         * @var \Symfony\Component\Translation\Translator $translator
         */
        $translator = $this->container->get('translator');

        if (false === $this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedHttpException();
        }

        if ($perspective->isExportable()) {
            $query = $this->executeQuery($perspective);

            if (is_null($query->error)) {
                $cipher = new AES();
                $key = $this->container->getParameter('aes_key');
                $iv = $this->container->getParameter('aes_iv');

                $cipher->setKey(hex2bin($key));
                $cipher->setIV(hex2bin($iv));
                $jsonEncodedRecords = json_encode($query->records);

                $missingCharacters = strlen($jsonEncodedRecords) % 16;
                $cipher->disablePadding();

                if ($missingCharacters > 0) {
                    $padding = str_repeat('0', 16 - $missingCharacters - 1) . '-';
                    $paddedSubject = $padding . $jsonEncodedRecords;
                    $paddingLength = strlen($padding);
                } else {
                    $paddedSubject = $jsonEncodedRecords;
                    $paddingLength = 0;
                }

                file_put_contents('/tmp/clear-text.txt', $paddedSubject);
                $encryptedRecords = $cipher->encrypt($paddedSubject);
                $base64EncodedRecords = base64_encode($encryptedRecords);
                file_put_contents('/tmp/enc', $base64EncodedRecords);

                return new JsonResponse([
                    'result' => $base64EncodedRecords,
                    'key' => $key,
                    'iv' => $iv,
                    'padding' => $paddingLength,
                    'type' => 'success'
                ]);
            } else {
                return new JsonResponse([
                    'result' => $query->error,
                    'type' => 'error'
                ]);
            }
        } else {
            throw new NotFoundHttpException($translator->trans('perspective.not_found', [], 'perspective', 'en'));
        }
    }

    /**
     * @param Perspective $perspective
     * @return \stdClass
     */
    protected function executeQuery(Perspective $perspective)
    {
        /**
         * @var \WeavingTheWeb\Bundle\DashboardBundle\DBAL\Connection $connection
         */
        $connection = $this->container->get('weaving_the_web_dashboard.dbal_connection');

        return $connection->executeQuery($perspective->getValue());
    }
} 