<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

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

        if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new WTW\CodeGeneration\QualityAssuranceBundle\WTWCodeGenerationQualityAssuranceBundle();
        }

        return $bundles;
    }

    /**
     * @return string
     */
    public function getRootDir()
    {
        return __DIR__;
    }

    /**
     * @return string
     */
    public function getCacheDir()
    {
        return dirname(__DIR__).'/app/cache/'.$this->getEnvironment();
    }

    /**
     * @return string
     */
    public function getLogDir()
    {
        return dirname(__DIR__).'/app/logs';
    }

    /**
     * @param LoaderInterface $loader
     * @throws Exception
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->setParameter('container.autowiring.strict_mode', true);
            $container->setParameter('container.dumper.inline_class_loader', true);
            $container->addObjectResource($this);
        });
        $loader->load($this->getRootDir().'/config/environment/config_'.$this->getEnvironment().'.yml');
    }
}
