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
        $results = [];
        $translator = $this->get('translator');
        $query = new \stdClass;
        $query->error = null;

        if ($request->request->has('query')) {
            $query->sql = $request->request->get('query');
        } else {
            $query= $this->getDefaultQuery();
        }

        /**
         * @var $connection Connection
         */
        $connection = $this->get('dashboard.dbal_connection');

        if ($connection->idempotentQuery($query->sql)) {
            try {
                if ($connection->pdoSafe($query->sql)) {
                    $results = $connection->delegateQueryExecution($query->sql);
                } else {
                    $results = $connection->connect()->execute($query->sql)->fetchResults();
                }
            } catch (\Exception $exception) {
                $query->error = $exception->getMessage();
            }
        } else {
            $results = [$translator->trans('requirement_valid_query', array(), 'messages')];
        }

        return $this->render(
            'WTWDashboardBundle:Document:showDocuments.html.twig', array(
                'error' => $query->error,
                'default_query' => $query->sql,
                'results' => $results,
                'title' => $translator->trans('title_documents')));
    }

    /**
     * @return string
     */
    public function getDefaultQuery()
    {
        /**
         * @var $connection Connection
         */
        $connection = $this->get('dashboard.dbal_connection');
        $database = $this->container->getParameter('database_name');
        $defaultQuery = new \stdClass();
        $defaultQuery->sql = 'invalid query';
        $defaultQuery->error = null;

        $baseQuery = <<< QUERY
SELECT per_value AS query
FROM {database}weaving_perspective
WHERE per_type = {type}
LIMIT 1
QUERY;

        $query = strtr($baseQuery, array(
            '{database}' => $database . '.',
            '{type}' => $connection::QUERY_TYPE_DEFAULT));

        try {
            $results = $connection->connect()->execute($query)->fetchResults();
            if (count($results) > 0) {
                $defaultQuery->sql = $results[0]['query'];
            }
        } catch (\Exception $exception) {
            $defaultQuery->error = $exception->getMessage();
        }

        return $defaultQuery;
    }
}