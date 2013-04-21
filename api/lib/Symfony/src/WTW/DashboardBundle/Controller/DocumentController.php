<?php

namespace WTW\DashboardBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Component\HttpFoundation\Request,
    Symfony\Bundle\FrameworkBundle\Controller\Controller;
use WTW\DashboardBundle\DBAL\Connection;

/**
 * Class DocumentController
 *
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @package WTW\DashboardBundle\Controller
 */
class DocumentController extends Controller
{
    /**
     * @Extra\Route("/documents", name="wtw_dashboard_show_documents")
     * @Extra\Method({"GET", "POST"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function showAction()
    {
        /**
         * @var $request Request
         */
        $error = null;
        $request = $this->get('request');
        $translator = $this->get('translator');

        /**
         * @var $connection Connection
         */
        $connection = $this->get('dashboard.dbal_connection');

        if ($request->request->has('query')) {
            $sql = $request->get('query');
        } else {
            $sql = $connection->getDefaultQuery();
        }

        $query = $connection->executeQuery($sql);

        return $this->render(
            'WTWDashboardBundle:Document:showDocuments.html.twig', array(
                'error' => $query->error,
                'default_query' => $query->sql,
                'records' => $query->records,
                'title' => $translator->trans('title_documents')));
    }
}