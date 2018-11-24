<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Loader;

/**
 * Registers bundles
 *
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class AppKernel extends Kernel
{
    /**
     * @return array|\Symfony\Component\HttpKernel\Bundle\BundleInterface[]
     */
    public function registerBundles()
    {
        $bundles = array(
            // Standard edition framework
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            // User management
            new WTW\UserBundle\WTWUserBundle(),
            new WeavingTheWeb\Bundle\UserBundle\WeavingTheWebUserBundle(),
            // API
            new WeavingTheWeb\Bundle\ApiBundle\WeavingTheWebApiBundle(),
            new WeavingTheWeb\Bundle\TwitterBundle\WeavingTheWebTwitterBundle(),
            // Search
            new WeavingTheWeb\Bundle\DashboardBundle\WeavingTheWebDashboardBundle(),
            // ORM
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            // AMQP consumers / producers
            new OldSound\RabbitMqBundle\OldSoundRabbitMqBundle(),
            new WeavingTheWeb\Bundle\AmqpBundle\WeavingTheWebAmqpBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new WTW\CodeGeneration\QualityAssuranceBundle\WTWCodeGenerationQualityAssuranceBundle();
        }

        return $bundles;
    }

    /**
     * @param LoaderInterface $loader
     * @throws Exception
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/config/environment/config_' . $this->getEnvironment() . '.yml');
    }
}
