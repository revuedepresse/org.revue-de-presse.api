<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

use WeavingTheWeb\Bundle\DashboardBundle\DBAL\Connection,
    WeavingTheWeb\Bundle\DashboardBundle\Exception\InvalidTableException,
    WeavingTheWeb\Bundle\DashboardBundle\Exception\InvalidQueryParametersException;

use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Handles SQL-based documents
 *
 * @package WeavingTheWeb\Bundle\DashboardBundle\Controller
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class DocumentController extends Controller
{
    /**
     * @param string $activeMenu
     * @param bool   $showSitemap
     *
     * @return Response
     *
     * @Extra\Route("/navigation/{activeMenu}", name="weaving_the_web_dashboard_show_navigation")
     * @Extra\Method({"GET"})
     */
    public function showNavigationAction($activeMenu = 'github_repositories', $showSitemap = false)
    {
        $response = $this->render('::navigation.html.twig', [
            'active_menu_item' => $activeMenu,
            'show_sitemap' => $showSitemap
        ]);

        /**
         * @var \Symfony\Component\Security\Core\SecurityContext $securityContext
         */
        $securityContext = $this->get('security.context');
        $token = $securityContext->getToken();

        if ($token instanceof AnonymousToken) {
            $response->setPublic();
            $response->setSharedMaxAge(3600*2);
        } else {
            $response->setPrivate();
            $response->setSharedMaxAge(0);
        }

        return $response;
    }

    /**
     * @Extra\Route("/documents", name="weaving_the_web_dashboard_show_documents")
     * @Extra\Method({"GET", "POST"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     *
     * @Secure(roles="ROLE_USER")
     */
    public function showDocumentsAction()
    {
        /**
         * @var $request Request
         */
        $request = $this->get('request');
        $translator = $this->get('translator');

        /**
         * @var $connection Connection
         */
        $connection = $this->get('weaving_the_web_dashboard.dbal_connection');
        $defaultQuery = $connection->getDefaultQuery();
        $query = 'SELECT 1';

        if ($request->request->has('query')) {
            $query = $request->request->get('query');
        } elseif (is_null($defaultQuery->error)) {
            $query = $defaultQuery->sql;
        }

        $query = $connection->executeQuery($query);

        return $this->render(
            'WeavingTheWebDashboardBundle:Document:showDocuments.html.twig', array(
                'active_menu_item' => 'dashboard',
                'error' => $query->error,
                'default_query' => $query->sql,
                'records' => $query->records,
                'title' => $translator->trans('title_documents')));
    }

    /**
     * @Extra\Route("/query", name="weaving_the_web_dashboard_save_query", options={"expose"=true})
     * @Extra\Method({"POST"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function saveQueryAction()
    {
        $request = $this->get('request');
        $translator = $this->get('translator');
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $type = 'error';

        if ($request->request->has('query')) {
            $error = null;
            $query = $request->request->get('query');
        } else {
            $error = $translator->trans('save_query_failure', array(), 'messages');
            $query = '';
        }

        /**
         * @var \WeavingTheWeb\Bundle\DashboardBundle\Repository\PerspectiveRepository  $perspectiveRepository
         * @var \Doctrine\ORM\EntityManager                                             $entityManager
         */
        $perspectiveRepository = $entityManager->getRepository('WeavingTheWebDashboardBundle:Perspective');
        $result = $perspectiveRepository->findBy(['value' => $query]);

        if (count($result) === 0) {
            try {
                $setters = $this->get('weaving_the_web_mapping.mapping');

                /** @var $perspective \WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective */
                $perspective = $perspectiveRepository->savePerspective($query, $setters);
                $entityManager->persist($perspective);
                $entityManager->flush();

                $result = $translator->trans('save_query_success', array('{{ query }}' => $query), 'messages');
                $type = 'success';
            } catch (\Exception $exception) {
                $result = $exception->getMessage();
            }
        } else {
            $result = $translator->trans('query_exists_already', array(), 'messages');
            $type = 'block';
        }

        return new Response(json_encode((object) array(
                'result' => is_null($error) ? $result : $error,
                'type' => $type
            )),
            201,
            array('Context-type' => 'application/json'));
    }

    /**
     * @Extra\Route(
     *      "/content/{table}/{key}/{column}",
     *      name="weaving_the_web_dashboard_save_content",
     *      requirements={
     *          "table" = "[_a-zA-Z]+",
     *          "key" = "[0-9]+",
     *          "column" = "[_a-zA-Z]+"
     *      },
     *      options={"expose"=true}
     * )
     * @Extra\Method({"POST"})
     *
     * @param Request $request
     * @param $table
     * @param $key
     * @param $column
     * @return JsonResponse
     */
    public function saveContentAction(Request $request, $table, $key, $column)
    {
        /**
         * @var \WeavingTheWeb\Bundle\DashboardBundle\Dbal\Connection $connection
         */
        $connection = $this->get('weaving_the_web_dashboard.dbal_connection');
        $content = $request->request->get('content');

        try {
            $records = $connection->saveContent($content, $table, $key, $column);
        } catch (InvalidQueryParametersException $exception) {
            return new JsonResponse([
                'result' => 'Sorry, your request is invalid.',
                'type' => 'error'
            ], 400);
        }

        if ($records) {
            return new JsonResponse([
                'result' => 'The content has been successfully saved',
                'type' => 'success'
            ]);
        }
    }
}
