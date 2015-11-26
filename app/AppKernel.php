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
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new JMS\AopBundle\JMSAopBundle(),
            new JMS\DiExtraBundle\JMSDiExtraBundle($this),
            new JMS\SecurityExtraBundle\JMSSecurityExtraBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            // User management
            new FOS\UserBundle\FOSUserBundle(),
            new Snc\RedisBundle\SncRedisBundle(),
            new WTW\UserBundle\WTWUserBundle(),
            new WeavingTheWeb\Bundle\UserBundle\WeavingTheWebUserBundle(),
            // API
            new FOS\OAuthServerBundle\FOSOAuthServerBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new FOS\TwitterBundle\FOSTwitterBundle(),
            new WeavingTheWeb\Bundle\ApiBundle\WeavingTheWebApiBundle(),
            new WeavingTheWeb\Bundle\TwitterBundle\WeavingTheWebTwitterBundle(),
            // Search
            new FOS\ElasticaBundle\FOSElasticaBundle(),
            new WeavingTheWeb\Bundle\MappingBundle\WeavingTheWebMappingBundle(),
            new WeavingTheWeb\Bundle\DashboardBundle\WeavingTheWebDashboardBundle(),
            // File upload
            new Oneup\UploaderBundle\OneupUploaderBundle(),
            // Bootstrap
            new Braincrafted\Bundle\BootstrapBundle\BraincraftedBootstrapBundle(),
            // ORM
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            // Documentation in Markdown
            new Knp\Bundle\MarkdownBundle\KnpMarkdownBundle(),
            // AMQP consumers / producers
            new OldSound\RabbitMqBundle\OldSoundRabbitMqBundle(),
            new WeavingTheWeb\Bundle\AmqpBundle\WeavingTheWebAmqpBundle(),
            // APC
            new Ornicar\ApcBundle\OrnicarApcBundle(),
            // Assets management
            new Sp\BowerBundle\SpBowerBundle(),
            // Routing
            new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
            // View helper
            new Liip\UrlAutoConverterBundle\LiipUrlAutoConverterBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test', 'bdd', 'box', 'cache'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            $bundles[] = new Elao\WebProfilerExtraBundle\WebProfilerExtraBundle();
        }

        return $bundles;
    }

    /**
     * @return string
     */
    public function getCacheDir()
    {
        if (in_array($this->environment, array('box'))) {
            return '/dev/shm/weaving-the-web/cache/' .  $this->environment;
        }

        return parent::getCacheDir();
    }

    /**
     * @return string
     */
    public function getLogDir()
    {
        if (in_array($this->environment, array('box'))) {
            return '/dev/shm/weaving-the-web/logs';
        }

        return parent::getLogDir();
    }

    /**
     * @param LoaderInterface $loader
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/config/environment/config_' . $this->getEnvironment() . '.yml');
    }
}
