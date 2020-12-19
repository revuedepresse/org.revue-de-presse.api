<?php

namespace App\Controller;

use Doctrine\Inflector\InflectorFactory;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\Common\Inflector\Inflector;
use FOS\RestBundle\Request\ParamFetcherInterface;

abstract class ResourceController extends Controller
{
    /**
     * @param $jsonResources
     *
     * @return array
     */
    public function classifyResources($jsonResources)
    {
        $classifiedResources = [];

        $inflectorFactory = InflectorFactory::create();
        $inflector = $inflectorFactory->build();

        if (!empty($jsonResources)) {
            $columnNames         = $this->getEntityColumnNames($jsonResources[0]);
            $mapper              = function ($jsonResource) use ($columnNames, $inflector) {
                foreach ($columnNames as $columnName) {
                    $classifiedColumnName                         = $inflector->classify($columnName);
                    $getter                                       = 'get' . $classifiedColumnName;
                    $properties[$inflector->tableize($columnName)] = $jsonResource->$getter();
                }

                return $properties;
            };
            $classifiedResources = array_map($mapper, $jsonResources);
        }

        return $classifiedResources;
    }

    /**
     * @param $entity
     *
     * @return array
     */
    public function getEntityColumnNames($entity)
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        return $entityManager->getClassMetadata(get_class($entity))->getFieldNames();
    }

    /**
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return array
     */
    public function validatesParameters(ParamFetcherInterface $paramFetcher)
    {
        $params = $paramFetcher->all();
        $page = $this->validateParameter($params, 'page');
        $limit = $this->validateParameter($params, 'limit');
        $itemsPerPage = $this->container
            ->getParameter('api_wtw_repositories_items_per_page');

        if ($page > 1) {
            $offset = $page * $itemsPerPage;
        } else {
            $offset = 0;
        }

        return array_merge(
            $params,
            array(
                'limit' => $limit,
                'offset' => $offset
            )
        );
    }

    /**
     * @param $params
     * @param $name
     *
     * @return null
     */
    public function validateParameter($params, $name)
    {
        if (false === array_key_exists($name, $params) ||
            strlen(trim($params[$name])) === 0) {
            $parameter = 1;
        } else {
            $parameter = $params[$name];
        }

        return $parameter;
    }
}
