<?php

namespace WTW\DashboardBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Component\HttpFoundation\Request;


/**
 * Class DocumentController
 *
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @package WTW\DashboardBundle\Controller
 */
class DocumentController extends Controller
{
    /**
     * @param null $query
     *
     * @Extra\Route("/documents/{query}", name="wtw_dashboard_show_documents")
     * @Extra\Method("GET|POST")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction($query = null)
    {
        /**
         * @var $request Request
         */
        $request = $this->get('request');
        $query = $request->request->get('query');

        $translator = $this->get('translator');
        $validQuery = $this->validateQuery($query);

        if ($validQuery) {
            /**
             * @var $em EntityManager
             */
            $em = $this->get('doctrine.orm.entitymanager');
            $stmt = $em->getConnection()->prepare($query);
            $stmt->execute();

            $rows[] = $stmt->fetchAll();
        } else {
$query = <<< QUERY
SELECT id, message
FROM weaving_preprod.weaving_facebook_status
LIMIT 0,100
QUERY;

            $rows = ['single scalar result'];
            $rows[] = $translator->trans('requirement_valid_query', array(), 'messages');
        }

        return $this->render(
            'WTWDashboardBundle:Document:showDocuments.html.twig', array(
                'default_query' => $query
                ,
                'rows' => $rows,
                'title' => $translator->trans('title_documents')));
    }

    /**
     * @param $query
     *
     * @return bool
     */
    public function validateQuery($query)
    {
        return
            (strlen($query) > 0) &&
            (false === strpos(strtolower($query), 'update')) &&
            (false === strpos(strtolower($query), 'insert')) &&
            (false === strpos(strtolower($query), 'delete')) &&
            (false === strpos(strtolower($query), 'truncate')) &&
            (false === strpos(strtolower($query), 'drop')) &&
            (false === strpos(strtolower($query), 'alter')) &&
            (false === strpos(strtolower($query), 'grant'));
    }
}