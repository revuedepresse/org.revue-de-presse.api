<?php

namespace WTW\DashboardBundle\Controller;

use FOS\TwitterBundle\Services\Twitter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Response;
use WTW\DashboardBundle\DBAL\Connection,
    WTW\DashboardBundle\Repository\PerspectiveRepository;

/**
 * Class DocumentController
 *
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @package WTW\DashboardBundle\Controller
 */
class DocumentController extends Controller
{
    /**
     * @Extra\Route("/twitter/connect", name="wtw_dashboard_twitter_connect")
     */
    public function connectTwitterAction()
    {
        $request = $this->get('request');
        $twitter = $this->get('fos_twitter.service');

        $authURL = $twitter->getLoginUrl($request);

        return new RedirectResponse($authURL);
    }

    /**
     * @Extra\Route("/twitter/login_check", name="wtw_dashboard_twitter_login_check")
     */
    public function loginCheckAction()
    {
        /**
         * @var $twitter Twitter
         */
        $twitter = $this->get('fos_twitter.service');
        $oauthToken = $this->getRequest()->get('oauth_token');
        $oauthVerifier = $this->getRequest()->get('oauth_verifier');
        $accessToken = $twitter->getAccessToken($oauthToken, $oauthVerifier);

        return new Response($accessToken);
    }

    /**
     * @Extra\Route("/could-not-login", name="wtw_dashboard_login_failure")
     */
    public function loginFailureAction()
    {
    }

    /**
     * @Extra\Route("/documents", name="wtw_dashboard_show_documents")
     * @Extra\Method({"GET", "POST"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function showDocumentsAction()
    {
        $error = null;
        /**
         * @var $request Request
         */
        $request = $this->get('request');
        $translator = $this->get('translator');

        /**
         * @var $connection Connection
         */
        $connection = $this->get('wtw.dashboard.dbal_connection');
        $defaultQuery = $connection->getDefaultQuery();
        $sql = 'SELECT 1';

        if ($request->request->has('query')) {
            $sql = $request->request->get('query');
        } elseif (is_null($defaultQuery->error)) {
            $sql = $defaultQuery->sql;
        }

        $query = $connection->executeQuery($sql);

        return $this->render(
            'WTWDashboardBundle:Document:showDocuments.html.twig', array(
                'active_menu_item' => 'dashboard',
                'error' => $query->error,
                'default_query' => $query->sql,
                'records' => $query->records,
                'title' => $translator->trans('title_documents')));
    }

    /**
     * @Extra\Route("/sql", name="wtw_dashboard_save_sql", options={"expose"=true})
     * @Extra\Method({"POST"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function saveSqlAction()
    {
        $request = $this->get('request');
        $translator = $this->get('translator');
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $perspectiveRepository = $entityManager->getRepository('WTWDashboardBundle:Perspective');
        $type = 'error';

        if ($request->request->has('sql')) {
            $error = null;
            $sql = $request->request->get('sql');
        } else {
            $error = $translator->trans('save_query_failure', array(), 'messages');
        }

        $result = $perspectiveRepository->findByValue($sql);

        if (count($result) === 0) {
            try {
                /**
                 * @var $perspective PerspectiveRepository
                 */
                $perspective = $perspectiveRepository->savePerspective($sql);
                $entityManager->persist($perspective);
                $entityManager->flush();

                $result = $translator->trans('save_query_success', array('{{ sql }}' => $sql), 'messages');
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
            200,
            array('Context-type' => 'application/json'));
    }
}