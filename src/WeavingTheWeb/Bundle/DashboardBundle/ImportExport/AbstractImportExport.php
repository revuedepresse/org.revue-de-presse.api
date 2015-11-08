<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\ImportExport;

abstract class AbstractImportExport
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    public $entityManager;

    /**
     * @var \WeavingTheWeb\Bundle\DashboardBundle\Security\CryptoInterface
     */
    public $crypto;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @var string
     */
    public $projectRootDir;
}
