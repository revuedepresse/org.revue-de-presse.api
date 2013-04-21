<?php

namespace WTW\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Component\HttpFoundation\Request;
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
        $request = $this->get('request');
        $translator = $this->get('translator');
        $query = $request->request->get('query');

        /**
         * @var $connection Connection
         */
        $connection = $this->get('dashboard.dbal_connection');

        if ($connection->idempotentQuery($query)) {
            if ($connection->pdoSafe($query)) {
                $entityManager = $this->get('doctrine.orm.entity_manager');
                $results = $connection->setEntityManager($entityManager)
                    ->delegateQueryExecution($query);
            } else {
                $results = $connection->connect()->execute($query)->fetchResults();
            }
        } else {
            $query = $this->getDefaultQuery();
            $results = ['single scalar result',
                $translator->trans('requirement_valid_query', array(), 'messages')];
        }

        return $this->render(
            'WTWDashboardBundle:Document:showDocuments.html.twig', array(
                'default_query' => $query,
                'results' => $results,
                'title' => $translator->trans('title_documents')));
    }

    /**
     * @return string
     */
    public function getDefaultQuery()
    {
        return <<< QUERY
SELECT id, message
FROM weaving_preprod.weaving_facebook_status
LIMIT 0,100
QUERY;
    }
}